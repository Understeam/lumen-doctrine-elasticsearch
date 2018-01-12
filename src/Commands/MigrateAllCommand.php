<?php
declare(strict_types=1);

namespace Understeam\LumenDoctrineElasticsearch\Commands;

use Illuminate\Console\Command;
use Understeam\LumenDoctrineElasticsearch\Migrations\MigrationRunner;

/**
 * Class MigrateCommand
 *
 * @author Anatoly Rugalev <anatoly.rugalev@gmail.com>
 */
class MigrateAllCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'doctrine:es:migrate:all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrates all available ES definitions';

    /**
     * @var MigrationRunner
     */
    protected $runner;

    public function __construct(MigrationRunner $runner)
    {
        parent::__construct();
        $this->runner = $runner;
    }

    /**
     * @throws \Exception
     */
    public function handle()
    {
        // TODO: single migrate command
        foreach ($this->runner->getRepositories() as $repositoryClass) {
            $definition = $this->runner->getDefinition($repositoryClass);
            $state = $this->runner->getDefinitionState($definition);
            switch ($state) {
                case MigrationRunner::STATE_NOT_MODIFIED:
                    $this->info($definition->getIndexAlias() . ": not modified");
                    break;
                case MigrationRunner::STATE_ABSENT:
                    $this->runner->createIndex($definition);
                    $this->info($definition->getIndexAlias() . ": created");
                    $this->info($definition->getIndexAlias() . ": importing...");
                    $this->importRepository($repositoryClass);
                    $this->info($definition->getIndexAlias() . ": import completed");
                    break;
                case MigrationRunner::STATE_SETTINGS_UPDATE_REQUIRED:
                    $this->runner->updateSettings($definition);
                    $this->info($definition->getIndexAlias() . ": settings updated");
                    break;
                case MigrationRunner::STATE_REINDEX_REQUIRED:
                    $this->info($definition->getIndexAlias() . ": reindexing...");
                    $this->runner->reindex($definition);
                    $this->info($definition->getIndexAlias() . ": reindex completed");
                    break;
                case MigrationRunner::STATE_REIMPORT_REQUIRED:
                    $this->info($definition->getIndexAlias() . ": reindexing...");
                    $this->runner->reindex($definition);
                    $this->info($definition->getIndexAlias() . ": reindex completed");
                    $this->info($definition->getIndexAlias() . ": importing...");
                    $this->importRepository($repositoryClass);
                    $this->info($definition->getIndexAlias() . ": import completed");
                    break;
            }
        }
    }

    protected function importRepository($repositoryClass)
    {
        $this->call("doctrine:es:import", [
            'repository' => $repositoryClass,
        ]);
    }
}
