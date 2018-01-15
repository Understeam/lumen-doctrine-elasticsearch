<?php
declare(strict_types=1);

namespace Understeam\LumenDoctrineElasticsearch\Search\Suggest;

/**
 * Class SuggestsCollection
 *
 * @author Anatoly Rugalev <anatoliy.rugalev@gs-labs.ru>
 */
class SuggestCollection implements SuggestCollectionContract
{
    /**
     * @var array
     */
    protected $suggests;

    public function __construct(array $data)
    {
        $this->suggests = $data;
    }

    /**
     * @return array
     */
    public function getSuggests(): array
    {
        return $this->suggests;
    }
}
