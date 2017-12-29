<?php
declare(strict_types=1);

namespace Understeam\LumenDoctrineElasticsearch;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Laravel\Scout\EngineManager;
use Nord\Lumen\Elasticsearch\Console\ApplyMigrationCommand;
use Nord\Lumen\Elasticsearch\Console\CreateCommand;
use Nord\Lumen\Elasticsearch\Console\CreateMigrationCommand;
use Nord\Lumen\Elasticsearch\Console\DeleteCommand;
use Nord\Lumen\Elasticsearch\Contracts\ElasticsearchServiceContract;
use Nord\Lumen\Elasticsearch\ElasticsearchServiceProvider;
use Understeam\LumenDoctrineElasticsearch\Definitions\DefinitionDispatcher;
use Understeam\LumenDoctrineElasticsearch\Definitions\DefinitionDispatcherContract;
use Understeam\LumenDoctrineElasticsearch\Engine\ElasticsearchEngine;
use Understeam\LumenDoctrineElasticsearch\Engine\ElasticsearchEngineContract;
use Understeam\LumenDoctrineElasticsearch\Migrations\Commands\MigrateAllCommand;

/**
 * Class ServiceProvider
 *
 * @author Anatoly Rugalev <anatoliy.rugalev@gs-labs.ru>
 */
class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Registers engine
     * @throws \Exception
     */
    public function register()
    {
        $this->registerDefinitionDispatcher();
        $this->registerEngine();
        $this->registerScoutEngine();
        $this->registerElasticsearch();
        $this->registerMigrations();
    }

    public function boot(Repository $config)
    {
        $this->registerDefinitions($config->get('doctrine_elasticsearch.definitions', []));
    }

    protected function registerEngine()
    {
        $this->app->bind(ElasticsearchEngineContract::class, ElasticsearchEngine::class);
    }

    protected function registerScoutEngine()
    {
        $this->app->make(EngineManager::class)->extend('elasticsearch', function () {
            return $this->app->make(ElasticsearchEngineContract::class);
        });
    }

    /**
     * @throws \Exception
     */
    protected function registerElasticsearch()
    {
        if (!$this->app->has(ElasticsearchServiceContract::class)) {
            throw new \Exception("You should register " . ElasticsearchServiceProvider::class . " before " . __CLASS__);
        }
        $this->commands([
            CreateCommand::class,
            DeleteCommand::class,
            CreateMigrationCommand::class,
            ApplyMigrationCommand::class,
        ]);
    }

    protected function registerDefinitionDispatcher()
    {
        $this->app->singleton(DefinitionDispatcherContract::class, DefinitionDispatcher::class);
    }

    protected function registerDefinitions($definitions)
    {
        $this->app->extend(
            DefinitionDispatcherContract::class,
            function (DefinitionDispatcherContract $dispatcher) use ($definitions) {
                foreach ($definitions as $definition) {
                    $dispatcher->addDefinition($this->app->make($definition));
                }
                return $dispatcher;
            }
        );
    }

    protected function registerMigrations()
    {
        $this->commands([
            MigrateAllCommand::class,
        ]);
    }
}
