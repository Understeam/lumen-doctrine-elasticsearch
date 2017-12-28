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
class MigrateCommand extends Command
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
        $this->runner->migrateAll();
    }
}
