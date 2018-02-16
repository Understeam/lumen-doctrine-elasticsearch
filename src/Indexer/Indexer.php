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
     * @throws IndexingException
     */
    public function updateEntities(IndexDefinitionContract $definition, array $entities): void
    {
        $bulk = [];
        foreach ($entities as $entity) {
            $document = $definition->getDocument($entity);
            // If document is null, considering as deleted
            if ($document !== null) {
                $bulk[] = [
                    'update' => [
                        '_id' => $definition->getDocumentKey($entity),
                        '_index' => $definition->getIndexAlias(),
                        '_type' => $definition->getTypeName(),
                    ],
                ];
                $bulk[] = [
                    'doc' => $document,
                    'doc_as_upsert' => true,
                ];
            } else {
                $bulk[] = [
                    'delete' => [
                        '_id' => $definition->getDocumentKey($entity),
                        '_index' => $definition->getIndexAlias(),
                        '_type' => $definition->getTypeName(),
                    ],
                ];
            }
        }
        $this->executeBulk($bulk);
    }

    /**
     * @inheritdoc
     * @throws IndexingException
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
        $this->executeBulk($bulk);
    }

    /**
     * @param array $bulk
     * @throws IndexingException
     */
    protected function executeBulk(array $bulk)
    {
        if (count($bulk)) {
            $result = $this->es->bulk(['body' => $bulk]);
            if ($result['errors'] > 0) {
                $json = json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                throw new IndexingException("Could not execute bulk request: {$result['errors']} errors detected.\n{$json}");
            }
        }
    }
}
