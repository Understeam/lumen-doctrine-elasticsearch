<?php

namespace Understeam\LumenDoctrineElasticsearch\Doctrine;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Illuminate\Support\Collection;
use Understeam\LumenDoctrineElasticsearch\Definitions\DefinitionDispatcherContract;
use Understeam\LumenDoctrineElasticsearch\Engine\ElasticsearchEngineContract;

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
     * @var ElasticsearchEngineContract
     */
    protected $engine;

    public function __construct(DefinitionDispatcherContract $definitions, ElasticsearchEngineContract $engine)
    {
        $this->definitions = $definitions;
        $this->engine = $engine;
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

    /**
     * @param array $objects
     */
    protected function indexEntities(array $objects)
    {
        // TODO: queuing
        $this->engine->update(new Collection($objects));
    }

    /**
     * @param array $objects
     */
    protected function removeEntities(array $objects)
    {
        // TODO: queueing
        $this->engine->update(new Collection($objects));
    }
}
