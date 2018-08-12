<?php
declare(strict_types=1);

namespace Understeam\Tests\LumenDoctrineElasticsearch;

use Understeam\LumenDoctrineElasticsearch\Doctrine\SearchableRepositoryContract;

/**
 * Class TestRepository
 *
 * @author Anatoly Rugalev <anatoliy.rugalev@gs-labs.ru>
 */
class TestRepository implements SearchableRepositoryContract
{

    private static $data;

    protected static function data()
    {
        if (!isset($data)) {
            $data = [];
            for ($i = 1; $i <= 200; $i++) {
                $data[$i] = new TestEntity($i, "Entity {$i}");
            }
            static::$data = $data;
        }
        return static::$data;
    }

    /**
     * Returns array of entities found by given ids in order of this ids
     * @param array $ids array of ids
     * @return array
     */
    public function findByIdsInOrder(array $ids): array
    {
        $data = static::data();
        $result = [];
        foreach ($ids as $id) {
            if (isset($data[$id])) {
                $result[] = $data[$id];
            }
        }
        return $result;
    }

    /**
     * Executes $callback for each models chunk of $size size
     * @param callable $callback
     * @param int $size
     */
    public function batch(callable $callback, $size = 100): void
    {
        $chunks = array_chunk(static::data(), 100, false);
        foreach ($chunks as $entities) {
            call_user_func($callback, $entities);
        }
    }

    /**
     * Returns index definition class
     * @return string
     */
    public static function getIndexDefinitionClass(): string
    {
        return TestIndexDefinition::class;
    }

    /**
     * Clears the repository, causing all managed entities to become detached.
     *
     * @return void
     */
    public function clear()
    {
    }
}
