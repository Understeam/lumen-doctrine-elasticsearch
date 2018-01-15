<?php
declare(strict_types=1);

namespace Understeam\LumenDoctrineElasticsearch\Search;

use Illuminate\Contracts\Container\Container;
use Understeam\LumenDoctrineElasticsearch\Search\Hits\HitsCollectionContract;
use Understeam\LumenDoctrineElasticsearch\Search\Suggest\SuggestCollectionContract;

/**
 * Class SearchResult
 *
 * @author Anatoly Rugalev <anatoly.rugalev@gmail.com>
 */
class SearchResult implements SearchResultContract
{

    /**
     * @var null|SuggestCollectionContract
     */
    protected $suggestions;

    /**
     * @var null|HitsCollectionContract
     */
    protected $hits;

    public function __construct(Container $container, array $data)
    {
        if (isset($data['hits'])) {
            $this->hits = $container->make(HitsCollectionContract::class, [
                'data' => $data['hits'],
            ]);
        }

        if (isset($data['suggest'])) {
            $this->suggestions = $container->make(SuggestCollectionContract::class, [
                'container' => $container,
                'data' => $data['suggest'],
            ]);
        }
    }

    /**
     * Returns suggestions
     * @return null|SuggestCollectionContract
     */
    public function getSuggestions(): ?SuggestCollectionContract
    {
        return $this->suggestions;
    }

    /**
     * Returns hits total count
     * @return null|HitsCollectionContract
     */
    public function getHits(): ?HitsCollectionContract
    {
        return $this->hits;
    }
}
