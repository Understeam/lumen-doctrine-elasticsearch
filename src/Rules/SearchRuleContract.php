<?php
declare(strict_types=1);

namespace Understeam\LumenDoctrineElasticsearch\Rules;

/**
 * Interface SearchRuleContract
 *
 * @author Anatoly Rugalev <anatoliy.rugalev@gs-labs.ru>
 */
interface SearchRuleContract
{

    public function isApplicable(): bool;

    public function buildQueryPayload(): array;

}
