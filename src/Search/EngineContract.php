<?php
declare(strict_types=1);

namespace Understeam\LumenDoctrineElasticsearch\Search;

use ONGR\ElasticsearchDSL\Search;
use Understeam\LumenDoctrineElasticsearch\Doctrine\SearchableRepositoryContract;
use Understeam\LumenDoctrineElasticsearch\Search\Aggs\AggsCollectionContract;
use Understeam\LumenDoctrineElasticsearch\Search\Suggest\SuggestCollectionContract;

/**
 * Interface EngineContract
 *
 * @author Anatoly Rugalev <anatoly.rugalev@gmail.com>
 */
interface EngineContract
{

    /**
     * Maps search results to entities via search repository
     * @param array $hits
     * @return object[] array of entities
     */
    public function mapHits(array $hits): array;

    /**
     * Executes any search request
     * @param Search $query
     * @return SearchResultContract search result
     */
    public function executeSearch(Search $query): SearchResultContract;

    /**
     * Executes search request and returns found hits
     * @param Search $query search request
     * @return object[] found entities
     */
    public function search(Search $query): array;

    /**
     * Executes search request and returns suggestions
     * @param Search $query
     * @return null|SuggestCollectionContract suggest collection
     */
    public function suggest(Search $query): ?SuggestCollectionContract;

    /**
     * Executes search request and returns aggregations
     * @param Search $query
     * @return null|AggsCollectionContract suggest collection
     */
    public function aggregate(Search $query): ?AggsCollectionContract;
}
