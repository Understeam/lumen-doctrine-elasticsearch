<?php
declare(strict_types=1);

namespace Understeam\LumenDoctrineElasticsearch\Definitions;

/**
 * Class AbstractDefinition
 *
 * @author Anatoly Rugalev <anatoliy.rugalev@gs-labs.ru>
 */
abstract class AbstractDefinition implements IndexDefinitionContract
{

    /**
     * @inheritdoc
     */
    public function getTypeName(): string
    {
        return $this->getIndexName();
    }

    /**
     * @inheritdoc
     */
    public function getDocumentKey($entity): string
    {
        return (string)$entity->getId();
    }
}
