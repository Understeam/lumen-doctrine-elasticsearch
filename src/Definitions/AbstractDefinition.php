<?php
declare(strict_types=1);

namespace Understeam\LumenDoctrineElasticsearch\Definitions;

/**
 * Class AbstractDefinition
 *
 * @author Anatoly Rugalev <anatoly.rugalev@gmail.com>
 */
abstract class AbstractDefinition implements IndexDefinitionContract
{

    /**
     * @inheritdoc
     */
    public function getTypeName(): string
    {
        return $this->getIndexAlias();
    }

    /**
     * @inheritdoc
     */
    public function getDocumentKey($entity): string
    {
        return (string)$entity->getId();
    }
}
