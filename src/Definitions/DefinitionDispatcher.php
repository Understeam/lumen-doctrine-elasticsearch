<?php
declare(strict_types=1);

namespace Understeam\LumenDoctrineElasticsearch\Definitions;

use Doctrine\Common\Util\ClassUtils;

/**
 * Class DefinitionManager
 *
 * @author Anatoly Rugalev <anatoliy.rugalev@gs-labs.ru>
 */
class DefinitionDispatcher implements DefinitionDispatcherContract
{

    /**
     * @var IndexDefinitionContract[]
     */
    protected $definitions = [];

    /**
     * @var IndexDefinitionContract[][]
     */
    protected $entityMap = [];

    /**
     * @var IndexDefinitionContract[]
     */
    protected $typeMap = [];

    /**
     * DefinitionDispatcher constructor.
     * @param IndexDefinitionContract[] $definitions
     */
    public function __construct(array $definitions = [])
    {
        foreach ($definitions as $definition) {
            $this->addDefinition($definition);
        }
    }

    /**
     * @inheritdoc
     */
    public function getDefinitions(): array
    {
        return $this->definitions;
    }

    /**
     * @inheritdoc
     */
    public function addDefinition(IndexDefinitionContract $definition): void
    {
        $this->definitions[get_class($definition)] = $definition;
        $this->entityMap[$definition->getEntityClass()][] = $definition;
        $this->typeMap[$definition->getIndexAlias() . '.' . $definition->getTypeName()] = $definition;
    }

    /**
     * @inheritdoc
     */
    public function getEntityDefinitions($entityClass): array
    {
        $entityClass = ClassUtils::getRealClass($entityClass);
        return $this->entityMap[$entityClass] ?? [];
    }

    /**
     * @inheritdoc
     */
    public function hasEntity($entityClass): bool
    {
        if (is_object($entityClass)) {
            $entityClass = ClassUtils::getClass($entityClass);
        }
        return !empty($this->entityMap[$entityClass]);
    }

    /**
     * @inheritdoc
     */
    public function getTypeDefinition(string $index, string $type): ?IndexDefinitionContract
    {
        return $this->typeMap["{$index}.{$type}"] ?? null;
    }

}
