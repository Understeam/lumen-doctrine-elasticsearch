<?php
declare(strict_types=1);

namespace Understeam\LumenDoctrineElasticsearch\Migrations\Commands;

use Illuminate\Console\Command;

/**
 * Class ImportCommand
 *
 * @author Anatoly Rugalev <anatoliy.rugalev@gs-labs.ru>
 */
class ImportCommand extends Command
{

    protected $signature = 'doctrine:es:import {class: Definition class}';

    protected $description = 'Imports all available data of given definition into Elasticsearch';

    public function handle()
    {

    }
}
