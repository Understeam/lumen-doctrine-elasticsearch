<?php
declare(strict_types=1);

namespace Understeam\LumenDoctrineElasticsearch\Search;

/**
 * Interface SearchResultContract
 *
 * @author Anatoly Rugalev <anatoly.rugalev@gmail.com>
 */
interface SearchResultContract
{

    public function getTotal(): int;

    public function getItems(): array;

}
