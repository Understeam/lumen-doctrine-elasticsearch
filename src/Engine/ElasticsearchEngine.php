<?php
declare(strict_types=1);

namespace Understeam\LumenDoctrineElasticsearch\Engine;

use Illuminate\Support\Collection;
use Laravel\Scout\Builder;
use Laravel\Scout\Engines\Engine;
use Nord\Lumen\Elasticsearch\Contracts\ElasticsearchServiceContract;
use Nord\Lumen\Elasticsearch\Documents\Bulk\BulkAction;
use Nord\Lumen\Elasticsearch\Documents\Bulk\BulkQuery;
use Understeam\LumenDoctrineElasticsearch\Builders\RuleBuilder;
use Understeam\LumenDoctrineElasticsearch\Definitions\DefinitionDispatcherContract;
use Understeam\LumenDoctrineElasticsearch\Payloads\IndexPayload;
use Understeam\LumenDoctrineElasticsearch\Payloads\TypePayload;

/**
 * Class ElasticsearchEngine
 *
 * @author Anatoly Rugalev <anatoliy.rugalev@gs-labs.ru>
 */
class ElasticsearchEngine extends Engine implements ElasticsearchEngineContract
{

    /**
     * @var ElasticsearchServiceContract
     */
    protected $service;

    /**
     * @var DefinitionDispatcherContract
     */
    protected $definitions;

    /**
     * ElasticsearchEngine constructor.
     * @param ElasticsearchServiceContract $service
     * @param DefinitionDispatcherContract $definitions
     */
    public function __construct(ElasticsearchServiceContract $service, DefinitionDispatcherContract $definitions)
    {
        $this->service = $service;
        $this->definitions = $definitions;
    }

    /**
     * @return BulkQuery
     */
    protected function makeBulkQuery()
    {
        // Bulk size doesn't really mean anything
        return new BulkQuery(0);
    }

    protected function makeBulkAction()
    {
        return new BulkAction();
    }

    /**
     * Update the given model in the index.
     * @param Collection $models
     * @return void
     */
    public function update($models)
    {
        $bulk = $this->makeBulkQuery();
        $models->each(function ($model) use ($bulk) {
            $definitions = $this->definitions->getEntityDefinitions(get_class($model));
            foreach ($definitions as $definition) {
                $action = $this->makeBulkAction();
                $action->setAction('update', [
                    '_id' => $definition->getDocumentKey($model),
                    '_index' => $definition->getIndexName(),
                    '_type' => $definition->getTypeName(),
                ]);
                $action->setBody([
                    'doc' => $definition->getDocument($model),
                    'doc_as_upsert' => true
                ]);
                $bulk->addAction($action);
            }
        });
        if ($bulk->hasItems()) {
            $this->service->bulk($bulk->toArray());
        }
    }

    /**
     * Remove the given model from the index.
     * @param  Collection $models
     * @return void
     */
    public function delete($models)
    {
        $bulk = $this->makeBulkQuery();
        $models->each(function ($model) use ($bulk, &$params) {
            $definitions = $this->definitions->getEntityDefinitions(get_class($model));
            foreach ($definitions as $definition) {
                $action = $this->makeBulkAction();
                $action->setAction('delete', [
                    '_id' => $definition->getDocumentKey($model),
                    '_index' => $definition->getIndexName(),
                    '_type' => $definition->getTypeName(),
                ]);
                $bulk->addAction($action);
            }
        });
        if ($bulk->hasItems()) {
            $this->service->bulk($bulk->toArray());
        }
    }

    protected function buildSearchQueryPayload(Builder $builder, $queryPayload, array $options = [])
    {
        // TODO: select definition
        foreach ($builder->wheres as $clause => $filters) {
            if (count($filters) == 0) {
                continue;
            }

            if (!array_has($queryPayload, 'filter.bool.' . $clause)) {
                array_set($queryPayload, 'filter.bool.' . $clause, []);
            }

            $queryPayload['filter']['bool'][$clause] = array_merge(
                $queryPayload['filter']['bool'][$clause],
                $filters
            );
        }

        $model = $builder->model;
        $payload = $this->makeTypePayload(
            $this->makeIndexPayload($model->searchableAs()),
            $model->searchableAs()
        )
            ->withValue('body.query.bool', $queryPayload)
            ->withValue('body.sort', $builder->orders)
            ->withValue('body.explain', $options['explain'] ?? null)
            ->withValue('body.profile', $options['profile'] ?? null);

        if ($size = isset($options['limit']) ? $options['limit'] : $builder->limit) {
            $payload->set('body.size', $size);

            if (isset($options['page'])) {
                $payload->set('body.from', ($options['page'] - 1) * $size);
            }
        }

        return $payload->toArray();
    }

    public function buildSearchQueryPayloadCollection(Builder $builder, array $options = [])
    {
        $payloadCollection = new Collection();

        if ($builder instanceof RuleBuilder) {
            $payloads = array_map(function ($payload) use ($options, $builder) {
                return $this->buildSearchQueryPayload($builder, $payload, $options);
            }, $builder->buildRulesPayloads());
            $payloadCollection->merge($payloads);
        } else {
            $payload = $this->buildSearchQueryPayload(
                $builder,
                ['must' => ['match_all' => new \stdClass()]],
                $options
            );

            $payloadCollection->push($payload);
        }

        return $payloadCollection;
    }

    protected function performSearch(Builder $builder, array $options = [])
    {
        if ($builder->callback) {
            return call_user_func($builder->callback, $this->service, $builder->query, $options);
        }

        $result = null;

        $this->buildSearchQueryPayloadCollection($builder, $options)->each(function ($payload) use (&$result) {
            $result = $this->service->search($payload);

            if ($this->getTotalCount($result) > 0) {
                return false;
            }
            return true;
        });

        return $result;
    }

    public function search(Builder $builder)
    {
        return $this->performSearch($builder);
    }

    public function paginate(Builder $builder, $perPage, $page)
    {
        return $this->performSearch($builder, [
            'limit' => $perPage,
            'page' => $page
        ]);
    }

    public function explain(Builder $builder)
    {
        return $this->performSearch($builder, [
            'explain' => true
        ]);
    }

    public function profile(Builder $builder)
    {
        return $this->performSearch($builder, [
            'profile' => true
        ]);
    }

    public function searchRaw($model, $query)
    {
        // TODO: select definition
        $payload = $this->makeTypePayload(
            $this->makeIndexPayload($model->searchableAs()),
            $model->searchableAs()
        )
            ->withValue('body', $query);

        return $this->service->search($payload->toArray());
    }

    public function mapIds($results)
    {
        return array_pluck($results['hits']['hits'], '_id');
    }

    public function map($results, $model)
    {
        // TODO
    }

    public function getTotalCount($results)
    {
        return $results['hits']['total'];
    }

    /**
     * @inheritdoc
     */
    public function get(Builder $builder)
    {
        $collection = parent::get($builder);

        if (isset($builder->with) && $collection->count() > 0) {
            $collection->load($builder->with);
        }

        return $collection;
    }

    protected function makeTypePayload($index, $name)
    {
        return new TypePayload($index, $name);
    }

    protected function makeIndexPayload($name)
    {
        return new IndexPayload($name);
    }
}
