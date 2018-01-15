<?php
declare(strict_types=1);

namespace Understeam\LumenDoctrineElasticsearch\Search\Hits;

/**
 * Class Hits
 *
 * @author Anatoly Rugalev <anatoliy.rugalev@gs-labs.ru>
 */
class HitsCollection implements HitsCollectionContract
{
    /**
     * @var int
     */
    protected $total;
    /**
     * @var array
     */
    protected $hits;
    /**
     * @var float
     */
    protected $maxScore;

    public function __construct(array $data)
    {
        $this->hits = (array)$data['hits'] ?? [];
        $this->total = (int)$data['total'] ?? 0;
        $this->maxScore = (float)$data['max_score'] ?? 0.0;
    }

    /**
     * @inheritdoc
     */
    public function getTotal(): int
    {
        return $this->total;
    }

    /**
     * @inheritdoc
     */
    public function getHits(): array
    {
        return $this->hits;
    }

    /**
     * @inheritdoc
     */
    public function getMaxScore(): float
    {
        return $this->maxScore;
    }
}
