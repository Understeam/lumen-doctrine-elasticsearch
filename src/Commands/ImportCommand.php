<?php
declare(strict_types=1);

namespace Understeam\LumenDoctrineElasticsearch\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Container\Container;
use Understeam\LumenDoctrineElasticsearch\Definitions\DefinitionDispatcherContract;
use Understeam\LumenDoctrineElasticsearch\Definitions\IndexDefinitionContract;
use Understeam\LumenDoctrineElasticsearch\Doctrine\SearchableRepositoryContract;
use Understeam\LumenDoctrineElasticsearch\Indexer\IndexerContract;

/**
 * Class ImportCommand
 *
 * @author Anatoly Rugalev <anatoly.rugalev@gmail.com>
 */
class ImportCommand extends Command
{

    protected $signature = 'doctrine:es:import {repository}';

    protected $description = 'Imports all available data of given definition into Elasticsearch';

    /**
     * @var DefinitionDispatcherContract
     */
    protected $definitions;
    /**
     * @var Container
     */
    protected $container;
    /**
     * @var IndexerContract
     */
    private $indexer;

    /**
     * ImportCommand constructor.
     * @param DefinitionDispatcherContract $definitions
     * @param Container $container
     * @param IndexerContract $indexer
     */
    public function __construct(
        DefinitionDispatcherContract $definitions,
        Container $container,
        IndexerContract $indexer
    ) {
        parent::__construct();
        $this->definitions = $definitions;
        $this->container = $container;
        $this->indexer = $indexer;
    }

    public function handle()
    {
        $repositoryClass = $this->argument('repository');
        $definition = $this->definitions->getRepositoryDefinition($repositoryClass);
        if ($definition === null) {
            $this->error("Index definition for '{$repositoryClass}' does not exist");
            return 1;
        }
        if (!$definition instanceof IndexDefinitionContract) {
            $this->error(get_class($definition), " is not index definition");
            return 1;
        }
        /** @var SearchableRepositoryContract $repository */
        $repository = $this->container->make($repositoryClass);
        if (!$repository instanceof SearchableRepositoryContract) {
            $this->error("Repository '{$repositoryClass}' should implement " . SearchableRepositoryContract::class);
            return 1;
        }
        $repository->batch(function ($entities) use ($repository, $definition) {
            $this->indexer->updateEntities($definition, $entities);
            $repository->clear();
        });
        return 0;
    }
}
