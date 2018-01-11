<?php
declare(strict_types=1);

namespace Understeam\LumenDoctrineElasticsearch\Search;

/**
 * Class SearchResult
 *
 * @author Anatoly Rugalev <anatoly.rugalev@gmail.com>
 */
class SearchResult implements SearchResultContract
{
    /**
     * @var int
     */
    protected $total;
    /**
     * @var array
     */
    protected $items;

    public function __construct(int $total, array $items)
    {
        $this->total = $total;
        $this->items = $items;
    }

    /**
     * @return int
     */
    public function getTotal(): int
    {
        return $this->total;
    }

    /**
     * @return array
     */
    public function getItems(): array
    {
        return $this->items;
    }
}
