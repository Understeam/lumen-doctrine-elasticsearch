<?php

namespace Understeam\Tests\LumenDoctrineElasticsearch\Unit;


class IndexerTest extends AbstractTest
{

    public function testIndexerUpdate()
    {
        $this->createIndex();
        $this->repository->batch(function ($entities) {
            $this->indexer->updateEntities($this->definition, $entities);
        });
    }

    public function testIndexerUpdateAndDelete()
    {
        $this->createIndex();
        $this->repository->batch(function ($entities) {
            $this->indexer->updateEntities($this->definition, $entities);
        });
        $this->repository->batch(function ($entities) {
            $this->indexer->deleteEntities($this->definition, $entities);
        });
    }
}
