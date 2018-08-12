<?php
declare(strict_types=1);

namespace Understeam\LumenDoctrineElasticsearch\Doctrine;

/**
 * Interface SearchableRepositoryContract
 *
 * @author Anatoly Rugalev <anatoly.rugalev@gmail.com>
 */
interface SearchableRepositoryContract
{

    /**
     * Returns array of entities found by given ids in order of this ids
     * @param array $ids array of ids
     * @return array
     */
    public function findByIdsInOrder(array $ids): array;

    /**
     * Executes $callback for each models chunk of $size size
     * @param callable $callback
     * @param int $size
     */
    public function batch(callable $callback, $size = 100): void;

    /**
     * Returns index definition class
     * @return string
     */
    public static function getIndexDefinitionClass(): string;

    /**
     * Clears the repository, causing all managed entities to become detached.
     *
     * @return void
     */
    public function clear();

}
