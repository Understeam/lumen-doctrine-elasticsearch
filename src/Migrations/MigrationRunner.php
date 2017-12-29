<?php
declare(strict_types=1);

namespace Understeam\LumenDoctrineElasticsearch\Migrations;

use \stdClass;
use Nord\Lumen\Elasticsearch\Contracts\ElasticsearchServiceContract;
use Understeam\LumenDoctrineElasticsearch\Definitions\DefinitionDispatcherContract;
use Understeam\LumenDoctrineElasticsearch\Definitions\IndexDefinitionContract;

/**
 * Class MigrationRunner
 *
 * @author Anatoly Rugalev <anatoliy.rugalev@gs-labs.ru>
 */
class MigrationRunner
{
    /**
     * @var DefinitionDispatcherContract
     */
    protected $definitions;
    /**
     * @var ElasticsearchServiceContract
     */
    protected $es;

    const STATE_NOT_MODIFIED = 'not-modified';
    const STATE_ABSENT = 'absent';
    const STATE_SETTINGS_CHANGED = 'settings-changed';
    const STATE_REINDEX_REQUIRED = 'reindex-required';

    /**
     * MigrationRunner constructor.
     * @param DefinitionDispatcherContract $definitions
     * @param ElasticsearchServiceContract $es
     */
    public function __construct(DefinitionDispatcherContract $definitions, ElasticsearchServiceContract $es)
    {
        $this->definitions = $definitions;
        $this->es = $es;
    }

    protected function getDefinitionBody(IndexDefinitionContract $definition)
    {
        return [
            'mappings' => [
                $definition->getTypeName() => $definition->getMapping(),
            ],
            'settings' => $definition->getSettings(),
        ];
    }

    /**
     * @deprecated
     * @param IndexDefinitionContract $definition
     * @return bool
     */
    protected function hasIndex(IndexDefinitionContract $definition)
    {
        return $this->es->indices()->exists(['index' => $definition->getIndexName()]);
    }

    protected function indexSuffix(string $indexName): string
    {
        return $indexName . '_' . time();
    }

    /**
     * @deprecated
     * @param array $currentState
     * @param array $expectedState
     * @return bool
     */
    protected function isIndexesEqual(array $currentState, array $expectedState): bool
    {
        // TODO: better difference analysis
        $currentState = [
            'mappings' => array_get($currentState, 'mappings', []),
            'settings' => array_get($currentState, 'settings', []),
        ];
        $diff = static::arrayDiff($expectedState, $currentState);
        return empty($diff);
    }

    protected static function arrayDiff($array1, $array2)
    {
        $difference = [];
        foreach ($array1 as $key => $value) {
            if (is_array($value)) {
                if (!isset($array2[$key]) || !is_array($array2[$key])) {
                    $difference[$key] = $value;
                } else {
                    $new_diff = static::arrayDiff($value, $array2[$key]);
                    if (!empty($new_diff)) {
                        $difference[$key] = $new_diff;
                    }
                }
            } else {
                if (!array_key_exists($key, $array2) || (string)$array2[$key] !== (string)$value) {
                    $difference[$key] = $value;
                }
            }
        }
        return $difference;
    }

    /**
     * @deprecated
     * @return IndexDefinitionContract[]
     */
    public function getNewDefinitions(): array
    {
        $new = [];
        foreach ($this->definitions->getDefinitions() as $definition) {
            if (!$this->hasIndex($definition)) {
                $new[] = $definition;
            }
        }
        return $new;
    }

    /**
     * @deprecated
     * @return IndexDefinitionContract[]
     */
    public function getMappingChanges(): array
    {
        $changed = [];
        foreach ($this->definitions->getDefinitions() as $definition) {
            /** @var array $currentState */
            $currentState = $this->es->indices()->get(['index' => $definition->getIndexName()]);
            $oldIndexName = array_keys($currentState)[0];
            $currentState = $currentState[$oldIndexName]['mappings'];
            $expectedState = $this->getDefinitionBody($definition)['mappings'];
            if (!$this->isIndexesEqual($currentState, $expectedState)) {
                $changed[] = $definition;
            }
        }
        return $changed;
    }

    /**
     * @deprecated
     * @return IndexDefinitionContract[]
     */
    public function getSettingsChanges(): array
    {
        $changed = [];
        foreach ($this->definitions->getDefinitions() as $definition) {
            /** @var array $currentState */
            $currentState = $this->es->indices()->get(['index' => $definition->getIndexName()]);
            $oldIndexName = array_keys($currentState)[0];
            $currentState = $currentState[$oldIndexName];
            $expectedState = $this->getDefinitionBody($definition);
            if (!$this->isIndexesEqual($currentState, $expectedState)) {
                $changed[] = $definition;
            }
        }
        return $changed;
    }

    /**
     * @deprecated
     * @param IndexDefinitionContract $definition
     * @return string
     */
    public function migrateDefinition(IndexDefinitionContract $definition): string
    {
        // TODO: cleanup
        $realIndexName = $this->indexSuffix($definition->getIndexName());
        if (!$this->hasIndex($definition)) {
            // Create new index with alias
            $this->es->indices()->create([
                'index' => $realIndexName,
                'body' => array_replace_recursive($this->getDefinitionBody($definition), [
                    'aliases' => [
                        $definition->getIndexName() => new stdClass(),
                    ],
                ]),
            ]);
        } else {
            /** @var array $currentState */
            $currentState = $this->es->indices()->get(['index' => $definition->getIndexName()]);
            $oldIndexName = array_keys($currentState)[0];
            // 1. Create new index without alias with temporary settings
            $this->es->indices()->create([
                'index' => $realIndexName,
                'body' => array_replace_recursive($this->getDefinitionBody($definition), [
                    'settings' => [
                        'refresh_interval' => -1,
                        'number_of_replicas' => 0,
                    ],
                ]),
            ]);
            // 2. Run Reindex API with nowait
            $task = $this->es->reindex([
                'wait_for_completion' => false,
                'body' => [
                    'source' => [
                        'index' => $definition->getIndexName(),
                        'size' => 500,
                    ],
                    'dest' => [
                        'index' => $realIndexName,
                    ],
                ],
            ]);
            // 3. Check reindex task status in loop
            do {
                $response = $this->es->tasks()->get([
                    'task_id' => $task['task']
                ]);

                sleep(1);
            } while ((bool)$response['completed'] === false);
            // 5. Update index settings from temporary
            $settings = $definition->getSettings();
            $this->es->indices()->putSettings([
                'index' => $realIndexName,
                'body' => [
                    'number_of_replicas' => array_get($settings, 'index.number_of_replicas'),
                    'refresh_interval' => array_get($settings, 'index.refresh_interval'),
                ],
            ]);
            // 6. Update index alias
            $this->es->indices()->updateAliases([
                'body' => [
                    'actions' => [
                        [
                            'add' => [
                                'index' => $realIndexName,
                                'alias' => $definition->getIndexName(),
                            ],
                        ],
                    ],
                ],
            ]);
            // 8. Refresh new index
            $this->es->indices()->refresh(['index' => $definition->getIndexName()]);
            // 9. Delete old index
            $this->es->indices()->delete(['index' => $oldIndexName]);
        }
        return $realIndexName;
    }

    protected function normalizeSettings(array $settings)
    {
        if (!isset($settings['index'])) {
            return ['index' => $settings];
        } else {
            return $settings;
        }
    }

    public function getDefinitionState(IndexDefinitionContract $definition): string
    {
        $indices = $this->es->indices();
        if (!$indices->exists(['index' => $definition->getIndexName()])) {
            return self::STATE_ABSENT;
        }
        /** @var array $index */
        $index = $indices->get(['index' => $definition->getIndexName()]);
        $realIndexName = array_keys($index)[0];
        $index = $index[$realIndexName];
        $mappings = $index['mappings'];
        $settings = $this->normalizeSettings($index['settings']);

        if ($this->isMappingsChanged($definition, $mappings)) {
            return self::STATE_REINDEX_REQUIRED;
        }
        if ($this->getChangedSettings($definition, $settings)) {
            foreach ($settings as $setting => $value) {
                if ($this->isSettingRequiresReindex($setting)) {
                    return self::STATE_REINDEX_REQUIRED;
                }
            }
            return self::STATE_SETTINGS_CHANGED;
        }

        return self::STATE_NOT_MODIFIED;
    }

    protected function isMappingsChanged(IndexDefinitionContract $definition, $expected)
    {
        $current = [$definition->getTypeName() => $definition->getMapping()];
        return !empty(self::arrayDiff($current, $expected)) || !empty(self::arrayDiff($expected, $current));
    }

    /**
     * Returns changed settings
     * @param IndexDefinitionContract $definition
     * @param $settings
     * @return array
     */
    protected function getChangedSettings(IndexDefinitionContract $definition, $settings)
    {
        // TODO: real implementation
        return $definition->getSettings();
    }

    /**
     * @param string $setting
     * @return bool
     */
    protected function isSettingRequiresReindex($setting)
    {
        // TODO: dot separator implementation
        $dynamicSettings = [
            'index.number_of_replicas',
            'index.auto_expand_replicas',
            'index.refresh_interval',
            'index.max_result_window',
            'index.max_inner_result_window',
            'index.max_rescore_window',
            'index.max_docvalue_fields_search',
            'index.max_script_fields',
            'index.max_ngram_diff',
            'index.max_shingle_diff',
            'index.blocks.read_only',
            'index.blocks.read_only_allow_delete',
            'index.blocks.read',
            'index.blocks.write',
            'index.blocks.metadata',
            'index.max_refresh_listeners',
        ];
        return in_array($setting, $dynamicSettings);
    }
}
