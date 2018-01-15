<?php
declare(strict_types=1);

namespace Understeam\LumenDoctrineElasticsearch\Definitions;

use Doctrine\Common\Util\ClassUtils;
use Illuminate\Contracts\Container\Container;
use Understeam\LumenDoctrineElasticsearch\Doctrine\SearchableRepositoryContract;
use Understeam\LumenDoctrineElasticsearch\Search\EngineContract;

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
     * @var Container
     */
    protected $container;

    /**
     * DefinitionDispatcher constructor.
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
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
    public function addRepository(string $repositoryClass): void
    {
        $definition = $this->createDefinition($repositoryClass::getIndexDefinitionClass());
        $this->repositoryMap[$repositoryClass] = $definition;
        $this->definitions[get_class($definition)] = $definition;
        $this->entityMap[$definition->getEntityClass()][] = $definition;
    }

    /**
     * @param string $class
     * @return IndexDefinitionContract
     */
    protected function createDefinition(string $class): IndexDefinitionContract
    {
        return $this->container->make($class);
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

    public function getEngine(SearchableRepositoryContract $repository): EngineContract
    {
        $definition = $this->getRepositoryDefinition(get_class($repository));
        return $this->container->make(EngineContract::class, [
            'container' => $this->container,
            'definition' => $definition,
            'repository' => $repository,
        ]);
    }

}
