<?php
declare(strict_types=1);

namespace Understeam\LumenDoctrineElasticsearch;

use Illuminate\Contracts\Config\Repository;
use Understeam\LumenDoctrineElasticsearch\Definitions\DefinitionDispatcher;
use Understeam\LumenDoctrineElasticsearch\Definitions\DefinitionDispatcherContract;
use Understeam\LumenDoctrineElasticsearch\Indexer\Indexer;
use Understeam\LumenDoctrineElasticsearch\Indexer\IndexerContract;
use Understeam\LumenDoctrineElasticsearch\Commands\ImportCommand;
use Understeam\LumenDoctrineElasticsearch\Commands\MigrateAllCommand;
use Understeam\LumenDoctrineElasticsearch\Search\Engine;
use Understeam\LumenDoctrineElasticsearch\Search\EngineContract;

/**
 * Class ServiceProvider
 *
 * @author Anatoly Rugalev <anatoly.rugalev@gmail.com>
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
        $this->registerIndexer();
        $this->registerMigrations();
    }

    public function boot(Repository $config)
    {
        $this->registerDefinitions($config->get('doctrine_elasticsearch.repositories', []));
    }

    protected function registerEngine()
    {
        $this->app->bind(EngineContract::class, Engine::class);
    }

    protected function registerIndexer()
    {
        $this->app->bind(IndexerContract::class, Indexer::class);
    }

    protected function registerDefinitionDispatcher()
    {
        $this->app->singleton(DefinitionDispatcherContract::class, DefinitionDispatcher::class);
    }

    protected function registerDefinitions($repositories)
    {
        $this->app->extend(
            DefinitionDispatcherContract::class,
            function (DefinitionDispatcherContract $dispatcher) use ($repositories) {
                foreach ($repositories as $repositoryClass) {
                    $dispatcher->addRepository($this->app->make($repositoryClass));
                }
                return $dispatcher;
            }
        );
    }

    protected function registerMigrations()
    {
        $this->commands([
            ImportCommand::class,
            MigrateAllCommand::class,
        ]);
    }
}
