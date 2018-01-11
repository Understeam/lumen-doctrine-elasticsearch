<?php
declare(strict_types=1);

namespace Understeam\LumenDoctrineElasticsearch\Search;

use Nord\Lumen\Elasticsearch\Contracts\ElasticsearchServiceContract;
use ONGR\ElasticsearchDSL\Search;
use Understeam\LumenDoctrineElasticsearch\Definitions\DefinitionDispatcherContract;
use Understeam\LumenDoctrineElasticsearch\Definitions\IndexDefinitionContract;
use Understeam\LumenDoctrineElasticsearch\Doctrine\SearchableRepositoryContract;

/**
 * Class Engine
 *
 * @author Anatoly Rugalev <anatoly.rugalev@gmail.com>
 */
class Engine implements EngineContract
{

    /**
     * @var ElasticsearchServiceContract
     */
    protected $es;
    /**
     * @var DefinitionDispatcherContract
     */
    protected $definitions;

    /**
     * Engine constructor.
     * @param ElasticsearchServiceContract $es
     * @param DefinitionDispatcherContract $definitions
     */
    public function __construct(ElasticsearchServiceContract $es, DefinitionDispatcherContract $definitions)
    {
        $this->es = $es;
        $this->definitions = $definitions;
    }

    /**
     * @inheritdoc
     */
    public function mapResults(SearchableRepositoryContract $repository, array $results): array
    {
        return $repository->findByIdsInOrder(array_column($results, '_id'));
    }

    /**
     * @param SearchableRepositoryContract $repository
     * @param Search $query
     * @return SearchResultContract
     */
    public function search(SearchableRepositoryContract $repository, Search $query): SearchResultContract
    {
        $definition = $this->definitions->getRepositoryDefinition(get_class($repository));
        $data = $this->executeSearch($definition, $query);
        $hits = $data['hits']['hits'] ?? [];
        if (count($hits)) {
            $total = $data['hits']['total'] ?? 0;
            $items = $this->mapResults($repository, $hits);
        } else {
            $total = 0;
            $items = [];
        }
        return new SearchResult($total, $items);
    }

    public function executeSearch(IndexDefinitionContract $definition, Search $query): array
    {
        return $this->es->search([
            '_source' => false,
            'index' => $definition->getIndexAlias(),
            'type' => $definition->getTypeName(),
            'body' => $query->toArray(),
        ]);
    }
}
