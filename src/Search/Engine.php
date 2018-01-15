<?php
declare(strict_types=1);

namespace Understeam\LumenDoctrineElasticsearch\Search;

use Illuminate\Contracts\Container\Container;
use Nord\Lumen\Elasticsearch\Contracts\ElasticsearchServiceContract;
use ONGR\ElasticsearchDSL\Search;
use Understeam\LumenDoctrineElasticsearch\Definitions\IndexDefinitionContract;
use Understeam\LumenDoctrineElasticsearch\Doctrine\SearchableRepositoryContract;
use Understeam\LumenDoctrineElasticsearch\Search\Suggest\SuggestCollectionContract;

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
     * @var IndexDefinitionContract
     */
    protected $definition;
    /**
     * @var Container
     */
    protected $container;
    /**
     * @var SearchableRepositoryContract
     */
    protected $repository;

    /**
     * Engine constructor.
     * @param ElasticsearchServiceContract $es
     * @param IndexDefinitionContract $definition
     * @param SearchableRepositoryContract $repository
     * @param Container $container
     */
    public function __construct(
        ElasticsearchServiceContract $es,
        IndexDefinitionContract $definition,
        SearchableRepositoryContract $repository,
        Container $container
    ) {
        $this->es = $es;
        $this->definition = $definition;
        $this->container = $container;
        $this->repository = $repository;
    }

    /**
     * @inheritdoc
     */
    public function mapHits(array $hits): array
    {
        if (!count($hits)) {
            return [];
        }
        return $this->repository->findByIdsInOrder(array_column($hits, '_id'));
    }

    /**
     * @inheritdoc
     */
    public function executeSearch(Search $query): SearchResultContract
    {
        $data = $this->es->search([
            '_source' => false,
            'index' => $this->definition->getIndexAlias(),
            'type' => $this->definition->getTypeName(),
            'body' => $query->toArray(),
        ]);
        return $this->container->make(SearchResultContract::class, [
            'container' => $this->container,
            'data' => $data,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function search(Search $query): array
    {
        $result = $this->executeSearch($query);
        return $this->mapHits($result->getHits()->getHits());
    }

    /**
     * @inheritdoc
     */
    public function suggest(Search $query): ?SuggestCollectionContract
    {
        $result = $this->executeSearch($query);
        return $result->getSuggestions();
    }
}
