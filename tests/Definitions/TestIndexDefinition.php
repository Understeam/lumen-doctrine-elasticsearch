<?php
declare(strict_types=1);

namespace Understeam\Tests\LumenDoctrineElasticsearch\Definitions;

use Understeam\LumenDoctrineElasticsearch\Definitions\AbstractDefinition;
use Understeam\Tests\LumenDoctrineElasticsearch\TestEntity;

/**
 * Class TestIndexDefinition
 *
 * @author Anatoly Rugalev <anatoliy.rugalev@gs-labs.ru>
 */
class TestIndexDefinition extends AbstractDefinition
{
    private $replicas = 1;
    private $shards = 2;
    private $mappingType = 'text';

    /**
     * Index name
     * @return string
     */
    public function getIndexAlias(): string
    {
        return 'test_index';
    }

    /**
     * Document type mapping
     * @return array
     */
    public function getMapping(): array
    {
        return [
            'properties' => [
                'name' => ['type' => $this->mappingType],
            ]
        ];
    }

    /**
     * Elasticsearch index settings
     *
     * Required options:
     * - index.number_of_replicas
     * - index.number_of_shards
     * - index.refresh_interval
     * @return array
     */
    public function getSettings(): array
    {
        return [
            'number_of_replicas' => $this->replicas,
            'number_of_shards' => $this->shards,
            'refresh_interval' => '2s',
        ];
    }

    public function setReplicas($replicas)
    {
        $this->replicas = $replicas;
    }

    public function setShards($shards)
    {
        $this->shards = $shards;
    }

    /**
     * Associated entity class
     * @return string
     */
    public function getEntityClass(): string
    {
        return TestEntity::class;
    }

    /**
     * Creates elasticsearch document based on associated entity
     * @param TestEntity|object $entity entity instance
     * @return array
     */
    public function getDocument($entity): array
    {
        return [
            'name' => $entity->getName(),
        ];
    }

    public function setPropertyType($string)
    {
        $this->mappingType = $string;
    }
}
