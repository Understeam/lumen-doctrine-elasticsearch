<?php
declare(strict_types=1);

namespace Understeam\Tests\LumenDoctrineElasticsearch;

/**
 * Class TestEntity
 *
 * @author Anatoly Rugalev <anatoliy.rugalev@gs-labs.ru>
 */
class TestEntity
{

    private $id;
    private $name;

    public function __construct($id, $name)
    {
        $this->id = $id;
        $this->name = $name;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

}
