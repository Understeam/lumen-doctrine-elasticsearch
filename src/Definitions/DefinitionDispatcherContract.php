<?php
declare(strict_types=1);

namespace Understeam\LumenDoctrineElasticsearch\Definitions;

use Understeam\LumenDoctrineElasticsearch\Doctrine\SearchableRepositoryContract;
use Understeam\LumenDoctrineElasticsearch\Search\EngineContract;

/**
 * Interface DefinitionDispatcherContract
 *
 * @author Anatoly Rugalev <anatoly.rugalev@gmail.com>
 */
interface DefinitionDispatcherContract
{

    /**
     * Returns all definitions
     * @return IndexDefinitionContract[]
     */
    public function getDefinitions(): array;

    /**
     * Returns definition by class name
     * @param string $class
     * @return null|IndexDefinitionContract
     */
    public function getDefinition(string $class): ?IndexDefinitionContract;

    /**
     * Adds definition instance to dispatcher
     * @param string|IndexDefinitionContract $repositoryClass
     */
    public function addRepository(string $repositoryClass): void;

    /**
     * Returns array of definitions associated with given entity
     * @param string $entityClass
     * @return IndexDefinitionContract[]
     */
    public function getEntityDefinitions($entityClass): array;

    /**
     * Checks whether given entity class has any definitions
     * @param string|object $entityClass
     * @return bool
     */
    public function hasEntity($entityClass): bool;

    /**
     * Returns definition associated with given repository class
     * @param $repositoryClass
     * @return null|IndexDefinitionContract
     */
    public function getRepositoryDefinition($repositoryClass): ?IndexDefinitionContract;

    /**
     * Returns all registered repository classes
     * @return string[]
     */
    public function getRepositoryClasses(): array;

    /**
     * Creates Engine for given repository
     * @param SearchableRepositoryContract $repository
     * @return EngineContract
     */
    public function getEngine(SearchableRepositoryContract $repository): EngineContract;

}
