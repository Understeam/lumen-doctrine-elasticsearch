<?php
declare(strict_types=1);

namespace Understeam\LumenDoctrineElasticsearch\Indexer;

use Understeam\LumenDoctrineElasticsearch\Definitions\IndexDefinitionContract;

/**
 * Interface ElasticsearchEngineContract
 *
 * @author Anatoly Rugalev <anatoly.rugalev@gmail.com>
 */
interface IndexerContract
{

    public function updateEntities(IndexDefinitionContract $definition, array $entities);

    public function deleteEntities(IndexDefinitionContract $definition, array $entities);

}
