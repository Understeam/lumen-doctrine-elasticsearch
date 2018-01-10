<?php
declare(strict_types=1);

namespace Understeam\Tests\LumenDoctrineElasticsearch\Unit;

use Codeception\Test\Unit;
use Elasticsearch\ClientBuilder;
use Nord\Lumen\Elasticsearch\ElasticsearchService;
use Understeam\LumenDoctrineElasticsearch\Definitions\DefinitionDispatcher;
use Understeam\LumenDoctrineElasticsearch\Migrations\MigrationRunner;
use Understeam\Tests\LumenDoctrineElasticsearch\Definitions\TestIndexDefinition;

/**
 * Class AbstractTest
 *
 * @author Anatoly Rugalev <anatoliy.rugalev@gs-labs.ru>
 */
abstract class AbstractTest extends Unit
{

    /**
     * @var \UnitTester
     */
    protected $tester;

    /**
     * @var MigrationRunner
     */
    protected $runner;

    /**
     * @var ElasticsearchService
     */
    protected $es;

    /**
     * @var TestIndexDefinition
     */
    protected $definition;

    protected function _before()
    {
        $this->definition = new TestIndexDefinition();
        $definitions = new DefinitionDispatcher([
            $this->definition,
        ]);
        $this->es = new ElasticsearchService(ClientBuilder::fromConfig(ES_CONFIG));
        $this->runner = new MigrationRunner(
            $definitions,
            $this->es
        );
        $this->deleteIndex();
    }

    protected function deleteIndex()
    {
        $this->es->indices()->delete(['index' => $this->definition->getIndexAlias() . '*']);
    }

    protected function createIndex()
    {
        $this->runner->createIndex($this->definition);
    }
}
