<?php

namespace Understeam\LumenDoctrineElasticsearch\Rules;

use Understeam\LumenDoctrineElasticsearch\Builders\RuleBuilder;

/**
 * Class SearchRule
 *
 * @original https://github.com/babenkoivan/scout-elasticsearch-driver
 * @author Anatoly Rugalev <anatoliy.rugalev@gs-labs.ru>
 */
class SearchRule implements SearchRuleContract
{
    protected $builder;

    public function __construct(RuleBuilder $builder)
    {
        $this->builder = $builder;
    }

    public function isApplicable(): bool
    {
        return true;
    }

    public function buildQueryPayload(): array
    {
        return [
            'must' => [
                'query_string' => [
                    'query' => $this->builder->query
                ]
            ]
        ];
    }
}
