<?php

namespace Understeam\LumenDoctrineElasticsearch\Doctrine;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Understeam\LumenDoctrineElasticsearch\Definitions\DefinitionDispatcherContract;
use Understeam\LumenDoctrineElasticsearch\Indexer\IndexerContract;

class SearchableSubscriber implements EventSubscriber
{
    /**
     * @var array
     */
    protected $toIndex = [];

    /**
     * @var array
     */
    protected $toDelete = [];

    /**
     * @var DefinitionDispatcherContract
     */
    protected $definitions;
    /**
     * @var IndexerContract
     */
    protected $indexer;

    public function __construct(DefinitionDispatcherContract $definitions, IndexerContract $indexer)
    {
        $this->definitions = $definitions;
        $this->indexer = $indexer;
    }

    /**
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [
            Events::postPersist,
            Events::postUpdate,
            Events::preRemove,
            Events::postFlush
        ];
    }

    public function postFlush()
    {
        $this->indexEntities($this->toIndex);
        $this->toIndex = [];

        $this->removeEntities($this->toDelete);
        $this->toDelete = [];
    }

    /**
     * @param LifecycleEventArgs $event
     */
    public function postPersist(LifecycleEventArgs $event)
    {
        if ($this->definitions->hasEntity($event->getObject())) {
            $this->scheduleIndexing($event);
        }
    }

    /**
     * @param LifecycleEventArgs $event
     */
    public function postUpdate(LifecycleEventArgs $event)
    {
        if ($this->definitions->hasEntity($event->getObject())) {
            $this->scheduleIndexing($event);
        }
    }

    /**
     * @param LifecycleEventArgs $event
     */
    public function preRemove(LifecycleEventArgs $event)
    {
        $object = $event->getObject();
        $this->toDelete[] = clone $object;
    }

    /**
     * @param LifecycleEventArgs $event
     */
    public function scheduleIndexing(LifecycleEventArgs $event)
    {
        $object = $event->getObject();
        $this->toIndex[] = $object;
    }

    protected function mapDefinitions(array $objects)
    {
        $result = [];
        foreach ($objects as $object) {
            $definitions = $this->definitions->getEntityDefinitions(get_class($object));
            foreach($definitions as $definition) {
                if (!$definition) {
                    continue;
                }
                $result[get_class($definition)][] = $object;
            }
        }
        return $result;
    }

    /**
     * @param array $objects
     */
    protected function indexEntities(array $objects)
    {
        // TODO: queueing
        $definitionMap = $this->mapDefinitions($objects);
        foreach ($definitionMap as $definitionClass => $objects) {
            $this->indexer->updateEntities($this->definitions->getDefinition($definitionClass), $objects);
        }
    }

    /**
     * @param array $objects
     */
    protected function removeEntities(array $objects)
    {
        // TODO: queueing
        $definitionMap = $this->mapDefinitions($objects);
        foreach ($definitionMap as $definitionClass => $objects) {
            $this->indexer->deleteEntities($this->definitions->getDefinition($definitionClass), $objects);
        }
    }
}
