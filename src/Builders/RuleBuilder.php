<?php

namespace Understeam\LumenDoctrineElasticsearch\Builders;

use Understeam\LumenDoctrineElasticsearch\Rules\SearchRuleContract;

/**
 * Class SearchBuilder
 *
 * @original https://github.com/babenkoivan/scout-elasticsearch-driver
 * @author Anatoly Rugalev <anatoliy.rugalev@gs-labs.ru>
 */
class RuleBuilder extends FilterBuilder
{
    /**
     * @var SearchRuleContract[]|callable[]
     */
    protected $rules = [];

    public function __construct($model, $query, $callback = null)
    {
        $this->query = $query;
        parent::__construct($model, $callback);
    }

    public function rule($rule)
    {
        $this->rules[] = $rule;

        return $this;
    }

    protected function instantiateRule($ruleClass)
    {
        return new $ruleClass();
    }

    public function buildRulesPayloads(): array
    {
        $payloads = [];
        foreach ($this->rules as $rule) {
            if (is_callable($rule)) {
                $payload = call_user_func($rule, $this);
            } else {
                // $rule is class name
                /** @var SearchRuleContract $ruleEntity */
                $ruleEntity = $this->instantiateRule($rule);

                if ($ruleEntity->isApplicable()) {
                    $payload = $ruleEntity->buildQueryPayload();
                } else {
                    continue;
                }
            }
            $payloads[] = $payload;
        }
        return $payloads;
    }
}
