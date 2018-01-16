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
use Understeam\LumenDoctrineElasticsearch\Search\Hits\HitsCollection;
use Understeam\LumenDoctrineElasticsearch\Search\Hits\HitsCollectionContract;
use Understeam\LumenDoctrineElasticsearch\Search\SearchResult;
use Understeam\LumenDoctrineElasticsearch\Search\SearchResultContract;
use Understeam\LumenDoctrineElasticsearch\Search\Suggest\SuggestCollection;
use Understeam\LumenDoctrineElasticsearch\Search\Suggest\SuggestCollectionContract;

/**
 * Class ServiceProvider
 *
 * @author Anatoly Rugalev <anatoly.rugalev@gmail.com>
 */
class ServiceProvider extends \Illuminate\Support\ServiceProvider
{

    protected $defer = true;

    /**
     * Registers engine
     * @throws \Exception
     */
    public function register()
    {
        $this->registerDefinitionDispatcher();
        $this->registerEngineManager();
        $this->registerIndexer();
        $this->registerMigrations();
        $this->registerSearchResult();
    }

    public function boot(Repository $config)
    {
        $this->registerDefinitions($config->get('doctrine_elasticsearch.repositories', []));
    }

    protected function registerEngineManager()
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

    protected function registerSearchResult()
    {
        $this->app->bind(SearchResultContract::class, SearchResult::class);
        $this->app->bind(HitsCollectionContract::class, HitsCollection::class);
        $this->app->bind(SuggestCollectionContract::class, SuggestCollection::class);
    }

    protected function registerDefinitions($repositories)
    {
        $this->app->extend(
            DefinitionDispatcherContract::class,
            function (DefinitionDispatcherContract $dispatcher) use ($repositories) {
                foreach ($repositories as $repositoryClass) {
                    $dispatcher->addRepository($repositoryClass);
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

    public function provides()
    {
        return [
            EngineContract::class,
            IndexerContract::class,
            DefinitionDispatcherContract::class,
            SearchResultContract::class,
            HitsCollectionContract::class,
            SuggestCollectionContract::class,
        ];
    }
}
