<?php
declare(strict_types=1);

namespace Understeam\LumenDoctrineElasticsearch\Migrations;

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
    private $es;

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

    public function migrateAll()
    {
        foreach ($this->definitions->getDefinitions() as $definition) {
            $this->migrateIndex($definition);
        }
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

    protected function hasIndex(IndexDefinitionContract $definition)
    {
        return $this->es->indices()->exists(['index' => $definition->getIndexName()]);
    }

    protected function createIndex(IndexDefinitionContract $definition)
    {
        $this->es->indices()->create([
            'index' => $definition->getIndexName(),
            'body' => $this->getDefinitionBody($definition),
        ]);
    }

    protected function migrateIndex($definition)
    {
        if (!$this->hasIndex($definition)) {
            $this->createIndex($definition);
            return;
        }
        // TODO: data migration via alias assignment and Reindex API
    }
}
