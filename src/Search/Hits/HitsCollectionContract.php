<?php
declare(strict_types=1);

namespace Understeam\LumenDoctrineElasticsearch\Search\Hits;

/**
 * Interface HitsContract
 *
 * @author Anatoly Rugalev <anatoliy.rugalev@gs-labs.ru>
 */
interface HitsCollectionContract
{

    /**
     * @return int
     */
    public function getTotal(): int;

    /**
     * @return array
     */
    public function getHits(): array;

    /**
     * @return float
     */
    public function getMaxScore(): float;

}
