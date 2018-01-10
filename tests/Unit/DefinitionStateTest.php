<?php


namespace Understeam\Tests\LumenDoctrineElasticsearch\Unit;

use Understeam\LumenDoctrineElasticsearch\Migrations\MigrationRunner;

class DefinitionStateTest extends AbstractTest
{
    public function testIndexCreate()
    {
        $state = $this->runner->getDefinitionState($this->definition);
        $this->assertEquals(MigrationRunner::STATE_ABSENT, $state);
        $indexName = $this->runner->createIndex($this->definition);
        $this->assertTrue($this->es->indices()->exists([
            'index' => $indexName
        ]));
        $this->assertTrue($this->es->indices()->existsAlias([
            'index' => $indexName,
            'name' => $this->definition->getIndexAlias()
        ]));
    }

    public function testIndexNotModified()
    {
        $this->createIndex();
        $state = $this->runner->getDefinitionState($this->definition);
        $this->assertEquals($state, MigrationRunner::STATE_NOT_MODIFIED);
    }

    public function testIndexDynamicSettingsUpdate()
    {
        $this->createIndex();
        $state = $this->runner->getDefinitionState($this->definition);
        $this->assertEquals($state, MigrationRunner::STATE_NOT_MODIFIED);
        $this->definition->setReplicas(50);
        $state = $this->runner->getDefinitionState($this->definition);
        $this->assertEquals($state, MigrationRunner::STATE_SETTINGS_UPDATE_REQUIRED);
        $this->runner->updateSettings($this->definition);
        $state = $this->runner->getDefinitionState($this->definition);
        $this->assertEquals($state, MigrationRunner::STATE_NOT_MODIFIED);
    }

    public function testIndexStaticSettingsUpdate()
    {
        $this->createIndex();
        $state = $this->runner->getDefinitionState($this->definition);
        $this->assertEquals($state, MigrationRunner::STATE_NOT_MODIFIED);
        $this->definition->setShards(5);
        $state = $this->runner->getDefinitionState($this->definition);
        $this->assertEquals($state, MigrationRunner::STATE_REINDEX_REQUIRED);
        $this->runner->reindex($this->definition);
        $state = $this->runner->getDefinitionState($this->definition);
        $this->assertEquals($state, MigrationRunner::STATE_NOT_MODIFIED);
    }

    public function testIndexReimportRequired()
    {
        $this->createIndex();
        $state = $this->runner->getDefinitionState($this->definition);
        $this->assertEquals($state, MigrationRunner::STATE_NOT_MODIFIED);
        $this->definition->setPropertyType('string');
        $state = $this->runner->getDefinitionState($this->definition);
        $this->assertEquals($state, MigrationRunner::STATE_REIMPORT_REQUIRED);
        $this->runner->reindex($this->definition);
        $state = $this->runner->getDefinitionState($this->definition);
        $this->assertEquals($state, MigrationRunner::STATE_NOT_MODIFIED);
    }

}
