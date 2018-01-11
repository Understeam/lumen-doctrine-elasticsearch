<?php
declare(strict_types=1);

namespace Understeam\LumenDoctrineElasticsearch\Definitions;

use Doctrine\Common\Util\ClassUtils;
use Understeam\LumenDoctrineElasticsearch\Doctrine\SearchableRepositoryContract;

/**
 * Class DefinitionManager
 *
 * @author Anatoly Rugalev <anatoly.rugalev@gmail.com>
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
    protected $repositoryMap = [];

    /**
     * DefinitionDispatcher constructor.
     * @param IndexDefinitionContract[] $repositories
     */
    public function __construct(array $repositories = [])
    {
        foreach ($repositories as $repository) {
            $this->addRepository($repository);
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
    public function getRepositoryClasses(): array
    {
        return array_keys($this->repositoryMap);
    }

    /**
     * @inheritdoc
     */
    public function getDefinition(string $class): ?IndexDefinitionContract
    {
        if (isset($this->definitions[$class])) {
            return $this->definitions[$class];
        } else {
            return null;
        }
    }

    /**
     * @inheritdoc
     */
    public function addRepository(SearchableRepositoryContract $repository): void
    {
        $definition = $this->createDefinition($repository->getIndexDefinitionClass());
        $this->repositoryMap[get_class($repository)] = $definition;
        $this->definitions[get_class($definition)] = $definition;
        $this->entityMap[$definition->getEntityClass()][] = $definition;
    }

    /**
     * @param string $class
     * @return IndexDefinitionContract
     */
    protected function createDefinition(string $class): IndexDefinitionContract
    {
        return new $class;
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
    public function getRepositoryDefinition($repositoryClass): ?IndexDefinitionContract
    {
        return $this->repositoryMap[$repositoryClass] ?? null;
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

}
