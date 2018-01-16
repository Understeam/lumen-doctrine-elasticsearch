<?php
declare(strict_types=1);

namespace Understeam\LumenDoctrineElasticsearch\Search\Aggs;

/**
 * Interface AggsCollectionContract
 *
 * @author Anatoly Rugalev <anatoliy.rugalev@gs-labs.ru>
 */
interface AggsCollectionContract
{

    /**
     * @return array
     */
    public function getAggs(): array;

}