<?php
declare(strict_types=1);

namespace Understeam\LumenDoctrineElasticsearch\Search\Suggest;

/**
 * Interface SuggestsCollectionContract
 *
 * @author Anatoly Rugalev <anatoliy.rugalev@gs-labs.ru>
 */
interface SuggestCollectionContract
{

    /**
     * @return array
     */
    public function getSuggests(): array;

}
