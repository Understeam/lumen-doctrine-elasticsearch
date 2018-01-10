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

    public function getId()
    {
        return 1;
    }

    public function getName()
    {
        return 'test-name';
    }

}
