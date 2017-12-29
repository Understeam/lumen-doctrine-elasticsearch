<?php
declare(strict_types=1);

namespace Understeam\LumenDoctrineElasticsearch\Definitions;

/**
 * Interface IndexDefinitionContract
 *
 * @author Anatoly Rugalev <anatoliy.rugalev@gs-labs.ru>
 */
interface IndexDefinitionContract
{

    /**
     * Index name
     * @return string
     */
    public function getIndexName(): string;

    /**
     * Type name
     * @return string
     */
    public function getTypeName(): string;

    /**
     * Document type mapping
     * @return array
     */
    public function getMapping(): array;

    /**
     * Elasticsearch index settings
     *
     * Required options:
     * - index.number_of_replicas
     * - index.number_of_shards
     * - index.refresh_interval
     * @return array
     */
    public function getSettings(): array;

    /**
     * Associated entity class
     * @return string
     */
    public function getEntityClass(): string;

    /**
     * Returns elasticsearch document id based on associated entity
     * @param object $entity entity instance
     * @return string
     */
    public function getDocumentKey($entity): string;

    /**
     * Creates elasticsearch document based on associated entity
     * @param object $entity entity instance
     * @return array
     */
    public function getDocument($entity): array;

}
