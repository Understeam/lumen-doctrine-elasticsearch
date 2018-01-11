<?php
declare(strict_types=1);

namespace Understeam\LumenDoctrineElasticsearch\Indexer;

use Nord\Lumen\Elasticsearch\Contracts\ElasticsearchServiceContract;
use Understeam\LumenDoctrineElasticsearch\Definitions\IndexDefinitionContract;

/**
 * Class ElasticsearchIndexer
 *
 * @author Anatoly Rugalev <anatoly.rugalev@gmail.com>
 */
class Indexer implements IndexerContract
{

    /**
     * @var ElasticsearchServiceContract
     */
    protected $es;

    /**
     * ElasticsearchEngine constructor.
     * @param ElasticsearchServiceContract $es
     */
    public function __construct(ElasticsearchServiceContract $es)
    {
        $this->es = $es;
    }

    /**
     * @inheritdoc
     */
    public function updateEntities(IndexDefinitionContract $definition, array $entities): void
    {
        $bulk = [];
        foreach ($entities as $entity) {
            $bulk[] = [
                'update' => [
                    '_id' => $definition->getDocumentKey($entity),
                    '_index' => $definition->getIndexAlias(),
                    '_type' => $definition->getTypeName(),
                ],
            ];
            $bulk[] = [
                'doc' => $definition->getDocument($entity),
                'doc_as_upsert' => true,
            ];
        }
        if (count($bulk)) {
            $this->es->bulk(['body' => $bulk]);
        }
    }

    /**
     * @inheritdoc
     */
    public function deleteEntities(IndexDefinitionContract $definition, array $entities): void
    {
        $bulk = [];
        foreach ($entities as $entity) {
            $bulk[] = [
                'delete' => [
                    '_id' => $definition->getDocumentKey($entity),
                    '_index' => $definition->getIndexAlias(),
                    '_type' => $definition->getTypeName(),
                ]
            ];
        }
        if ($bulk) {
            $this->es->bulk(['body' => $bulk]);
        }
    }
}
