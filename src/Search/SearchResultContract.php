<?php
declare(strict_types=1);

namespace Understeam\LumenDoctrineElasticsearch\Search;

use Understeam\LumenDoctrineElasticsearch\Search\Hits\HitsCollectionContract;
use Understeam\LumenDoctrineElasticsearch\Search\Suggest\SuggestCollectionContract;

/**
 * Interface SearchResultContract
 *
 * @author Anatoly Rugalev <anatoly.rugalev@gmail.com>
 */
interface SearchResultContract
{

    /**
     * Returns hits total count
     * @return null|HitsCollectionContract
     */
    public function getHits(): ?HitsCollectionContract;

    /**
     * Returns suggestions
     * @return null|SuggestCollectionContract
     */
    public function getSuggestions(): ?SuggestCollectionContract;

}
