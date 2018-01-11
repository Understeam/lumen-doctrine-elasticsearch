<?php
declare(strict_types=1);

namespace Understeam\LumenDoctrineElasticsearch\Search;

use ONGR\ElasticsearchDSL\Search;
use Understeam\LumenDoctrineElasticsearch\Definitions\IndexDefinitionContract;
use Understeam\LumenDoctrineElasticsearch\Doctrine\SearchableRepositoryContract;

/**
 * Interface EngineContract
 *
 * @author Anatoly Rugalev <anatoly.rugalev@gmail.com>
 */
interface EngineContract
{

    public function mapResults(SearchableRepositoryContract $repository, array $results): array;

    public function search(SearchableRepositoryContract $repository, Search $query): SearchResultContract;

    public function executeSearch(IndexDefinitionContract $definition, Search $query): array;
}