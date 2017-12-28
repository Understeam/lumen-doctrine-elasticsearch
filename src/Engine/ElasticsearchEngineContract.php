<?php
declare(strict_types=1);

namespace Understeam\LumenDoctrineElasticsearch\Engine;

use Laravel\Scout\Builder;

/**
 * Interface ElasticsearchEngineContract
 *
 * @author Anatoly Rugalev <anatoliy.rugalev@gs-labs.ru>
 */
interface ElasticsearchEngineContract
{

    public function update($models);

    public function delete($models);

    public function search(Builder $builder);

    public function paginate(Builder $builder, $perPage, $page);
}
