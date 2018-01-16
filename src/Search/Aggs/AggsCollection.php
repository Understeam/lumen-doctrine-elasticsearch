<?php
declare(strict_types=1);

namespace Understeam\LumenDoctrineElasticsearch\Search\Aggs;

/**
 * Class AggsCollection
 *
 * @author Anatoly Rugalev <anatoliy.rugalev@gs-labs.ru>
 */
class AggsCollection implements AggsCollectionContract
{

    /**
     * @var array
     */
    protected $aggs;

    public function __construct(array $data)
    {
        $this->aggs = $data;
    }

    /**
     * @return array
     */
    public function getAggs(): array
    {
        return $this->aggs;
    }
}
