<?php
declare(strict_types=1);

namespace Understeam\LumenDoctrineElasticsearch\Definitions;

/**
 * Interface DefinitionDispatcherContract
 *
 * @author Anatoly Rugalev <anatoliy.rugalev@gs-labs.ru>
 */
interface DefinitionDispatcherContract
{

    /**
     * Returns all definitions
     * @return IndexDefinitionContract[]
     */
    public function getDefinitions(): array;

    /**
     * Adds definition instance to dispatcher
     * @param IndexDefinitionContract $definition
     */
    public function addDefinition(IndexDefinitionContract $definition): void;

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
     * Returns definition which defines given index type
     * @param string $index
     * @param string $type
     * @return null|IndexDefinitionContract
     */
    public function getTypeDefinition(string $index, string $type): ?IndexDefinitionContract;

}
