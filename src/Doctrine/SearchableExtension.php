<?php

namespace Understeam\LumenDoctrineElasticsearch\Doctrine;

use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityManagerInterface;
use LaravelDoctrine\ORM\Extensions\Extension;
use Understeam\LumenDoctrineElasticsearch\Definitions\DefinitionDispatcherContract;
use Understeam\LumenDoctrineElasticsearch\Indexer\IndexerContract;

class SearchableExtension implements Extension
{
    /**
     * @var DefinitionDispatcherContract
     */
    protected $definitions;

    /**
     * @var IndexerContract
     */
    protected $engine;

    /**
     * @param DefinitionDispatcherContract $definitions
     * @param IndexerContract $engine
     */
    public function __construct(DefinitionDispatcherContract $definitions, IndexerContract $engine)
    {
        $this->definitions = $definitions;
        $this->engine = $engine;
    }

    /**
     * @param EventManager           $manager
     * @param EntityManagerInterface $em
     * @param Reader|null            $reader
     */
    public function addSubscribers(EventManager $manager, EntityManagerInterface $em, Reader $reader = null)
    {
        $manager->addEventSubscriber(new SearchableSubscriber($this->definitions, $this->engine));
    }

    /**
     * @return array
     */
    public function getFilters()
    {
        return [];
    }
}
