<?php
declare(strict_types=1);

namespace Understeam\LumenDoctrineElasticsearch\Migrations\Commands;

use Illuminate\Console\Command;
use Understeam\LumenDoctrineElasticsearch\Migrations\MigrationRunner;

/**
 * Class MigrateCommand
 *
 * @author Anatoly Rugalev <anatoliy.rugalev@gs-labs.ru>
 */
class MigrateAllCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'doctrine:elastic:migrate:all';

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

    public function handle()
    {
        $new = $this->runner->getNewDefinitions();
        if (count($new)) {
            foreach ($this->runner->getNewDefinitions() as $definition) {
                $this->info("Creating index for " . get_class($definition) . "...");
                $index = $this->runner->migrateDefinition($definition);
                $this->info("Created index {$index}.");
            }
        } else {
            $this->info("No new definitions found.");
        }
        $changed = $this->runner->getMappingChanges();
        if (count($changed)) {
            foreach ($this->runner->getMappingChanges() as $definition) {
                $this->info("Migrating index for " . get_class($definition) . "...");
                $index = $this->runner->migrateDefinition($definition);
                $this->info("Migrated index to {$index}.");
            }
        } else {
            $this->info("No changed definitions detected.");
        }
    }
}
