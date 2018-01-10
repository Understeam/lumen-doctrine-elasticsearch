<?php
declare(strict_types=1);

namespace Understeam\LumenDoctrineElasticsearch\Doctrine;

use Understeam\LumenDoctrineElasticsearch\Definitions\IndexDefinitionContract;

/**
 * Interface SearchableRepositoryContract
 *
 * @author Anatoly Rugalev <anatoliy.rugalev@gs-labs.ru>
 */
interface SearchableRepositoryContract
{

    /**
     * @return IndexDefinitionContract
     */
    public function getIndexDefinition(): IndexDefinitionContract;

}
