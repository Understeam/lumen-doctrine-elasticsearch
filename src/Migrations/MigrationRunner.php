<?php
declare(strict_types=1);

namespace Understeam\LumenDoctrineElasticsearch\Migrations;

use Illuminate\Support\Arr;
use Nord\Lumen\Elasticsearch\Contracts\ElasticsearchServiceContract;
use Understeam\LumenDoctrineElasticsearch\Definitions\DefinitionDispatcherContract;
use Understeam\LumenDoctrineElasticsearch\Definitions\IndexDefinitionContract;

/**
 * Class MigrationRunner
 *
 * @author Anatoly Rugalev <anatoly.rugalev@gmail.com>
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

    const STATE_ABSENT = 'absent';
    const STATE_NOT_MODIFIED = 'not-modified';
    const STATE_SETTINGS_UPDATE_REQUIRED = 'settings-update-required';
    const STATE_REINDEX_REQUIRED = 'reindex-required';
    const STATE_REIMPORT_REQUIRED = 'reimport-required';

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

    /**
     * @return string[]
     */
    public function getRepositories()
    {
        return $this->definitions->getRepositoryClasses();
    }

    public function getDefinition($repositoryClass): IndexDefinitionContract
    {
        return $this->definitions->getRepositoryDefinition($repositoryClass);
    }

    protected function getDefinitionBody(IndexDefinitionContract $definition)
    {
        return [
            'mappings' => [
                $definition->getTypeName() => array_replace_recursive($definition->getMapping(), [
                    '_meta' => $this->getMappingMeta($definition),
                ]),
            ],
            'settings' => $definition->getSettings(),
        ];
    }

    protected function getDocumentMethodSignature(IndexDefinitionContract $definition)
    {
        $class = new \ReflectionClass($definition);
        $method = $class->getMethod('getDocument');
        $start = $method->getStartLine();
        $end = $method->getEndLine();
        $file = $class->getFileName();
        $lines = array_slice(file($file), $start, $end - $start);
        return sha1(trim(implode("\n", $lines)));
    }

    protected function getMappingMeta(IndexDefinitionContract $definition)
    {
        return [
            // We need source data to detect whether it was changed
            'definitionClass' => get_class($definition),
            'entityClass' => $definition->getEntityClass(),
            'mappings' => serialize($definition->getMapping()),
            'settings' => serialize($definition->getSettings()),
            'signature' => $this->getDocumentMethodSignature($definition),
        ];
    }

    protected function indexSuffix(string $indexName): string
    {
        return $indexName . '_' . uniqid() . '_' . date('Y-m-d_H-i');
    }

    protected function getRealIndexName(string $alias)
    {
        /** @var array $data */
        $data = $this->es->indices()->get(['index' => $alias]);
        return Arr::first(array_keys($data));
    }

    /**
     * @param IndexDefinitionContract $definition
     * @return string
     * @throws \Exception
     */
    public function getDefinitionState(IndexDefinitionContract $definition): string
    {
        $indices = $this->es->indices();
        if (!$indices->exists(['index' => $definition->getIndexAlias()])) {
            return self::STATE_ABSENT;
        }
        $realName = $this->getRealIndexName($definition->getIndexAlias());
        $mapping = $this->es->indices()->getMapping([
            'index' => $definition->getIndexAlias(),
            'type' => $definition->getTypeName()
        ]);

        $meta = $mapping[$realName]['mappings'][$definition->getIndexAlias()]['_meta'] ?? null;
        if ($meta === null) {
            throw new \Exception("Index {$realName} was not created by this migrations. Delete or rename index to continue");
        }
        if (!isset($meta['signature']) || $meta['signature'] != $this->getDocumentMethodSignature($definition)) {
            // getDocument() method was changed
            return self::STATE_REIMPORT_REQUIRED;
        }
        $originalMappings = unserialize($meta['mappings']);
        if ($originalMappings !== $definition->getMapping()) {
            // Mapping was changed
            return self::STATE_REIMPORT_REQUIRED;
        }

        $originalSettings = unserialize($meta['settings']);
        if ($originalSettings !== $definition->getSettings()) {
            if ($this->wasStaticSettingsChanged($originalSettings, $definition->getSettings())) {
                // Static settings were changed
                return self::STATE_REINDEX_REQUIRED;
            } else {
                // Only dynamic settings were changed
                return self::STATE_SETTINGS_UPDATE_REQUIRED;
            }
        }

        return self::STATE_NOT_MODIFIED;
    }

    /**
     * Creates new index.
     * @param IndexDefinitionContract $definition
     * @return string
     */
    public function createIndex(IndexDefinitionContract $definition): string
    {
        $realIndexName = $this->indexSuffix($definition->getIndexAlias());
        $this->es->indices()->create([
            'index' => $realIndexName,
            'body' => array_replace_recursive($this->getDefinitionBody($definition), [
                'aliases' => [
                    $definition->getIndexAlias() => new \stdClass(),
                ],
            ]),
        ]);
        return $realIndexName;
    }

    /**
     * Updates index settings
     * @param IndexDefinitionContract $definition
     */
    public function updateSettings(IndexDefinitionContract $definition)
    {
        $settings = $definition->getSettings();
        $settings = $this->filterStaticSettings($settings);
        // Trying to update settings as given
        $this->es->indices()->putSettings([
            'index' => $definition->getIndexAlias(),
            'body' => $settings,
        ]);
        $this->es->indices()->putMapping([
            'index' => $definition->getIndexAlias(),
            'type' => $definition->getTypeName(),
            'body' => [
                '_meta' => $this->getMappingMeta($definition),
            ]
        ]);
    }


    /**
     * Recreates index and moves data into new index
     * @param IndexDefinitionContract $definition
     */
    public function reindex(IndexDefinitionContract $definition)
    {
        $realIndexName = $this->indexSuffix($definition->getIndexAlias());
        // 0. Retrieve real index name (not alias)
        /** @var array $currentState */
        $currentState = $this->es->indices()->get(['index' => $definition->getIndexAlias()]);
        $oldIndexName = array_keys($currentState)[0];
        // 1. Create new index without alias with temporary settings to speedup reindex
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
                    'index' => $definition->getIndexAlias(),
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
        $settings = static::normalizeSettings($definition->getSettings());
        $this->es->indices()->putSettings([
            'index' => $realIndexName,
            'body' => [
                'number_of_replicas' => array_get($settings, 'index.number_of_replicas'),
                'refresh_interval' => array_get($settings, 'index.refresh_interval'),
            ],
        ]);
        // 6. Switch index alias
        $this->es->indices()->updateAliases([
            'body' => [
                'actions' => [
                    [
                        'add' => [
                            'index' => $realIndexName,
                            'alias' => $definition->getIndexAlias(),
                        ],
                    ],
                    [
                        'remove' => [
                            'index' => $oldIndexName,
                            'alias' => $definition->getIndexAlias(),
                        ],
                    ],
                ],
            ],
        ]);
        // 8. Refresh new index
        $this->es->indices()->refresh(['index' => $definition->getIndexAlias()]);
        // 9. Delete old index
        $this->es->indices()->delete(['index' => $oldIndexName]);
    }

    protected static function getDynamicSettings()
    {
        return [
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
    }

    protected static function normalizeSettings($settings)
    {
        if (empty($settings['index'])) {
            $settings = ['index' => $settings];
        }
        // Unset index-specific properties
        $unset = [
            'provided_name',
            'creation_date',
            'uuid',
            'version',
        ];
        foreach ($unset as $property) {
            unset($settings['index'][$property]);
        }
        return $settings;
    }

    protected static function wasStaticSettingsChanged($current, $new): bool
    {
        $current = static::normalizeSettings($current);
        $new = static::normalizeSettings($new);
        $diff = static::arrayDiff($current, $new);
        if (!count($diff)) {
            return false;
        }
        $diff = static::arrayFlatten($diff);
        $staticSettings = array_diff(array_keys($diff), static::getDynamicSettings());
        return count($staticSettings) > 0;
    }

    /**
     * Excludes static settings from settings array
     * @param $settings
     * @return array
     */
    protected static function filterStaticSettings($settings): array
    {
        $settings = static::normalizeSettings($settings);
        $dynamicSettings = static::getDynamicSettings();
        $settings = static::arrayFlatten($settings);
        foreach ($settings as $key => $value) {
            if (!in_array($key, $dynamicSettings)) {
                unset($settings[$key]);
            }
        }
        return $settings;
    }


    protected static function arrayFlatten(array $a): array
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($a));
        $result = [];
        foreach ($iterator as $leafValue) {
            $keys = [];
            foreach (range(0, $iterator->getDepth()) as $depth) {
                $keys[] = $iterator->getSubIterator($depth)->key();
            }
            $result[implode('.', $keys)] = $leafValue;
        }
        return $result;
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
}
