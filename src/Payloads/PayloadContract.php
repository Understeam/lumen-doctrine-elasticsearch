<?php
declare(strict_types=1);

namespace Understeam\LumenDoctrineElasticsearch\Payloads;

use Illuminate\Contracts\Support\Arrayable;

/**
 * Interface PayloadContract
 *
 * @author Anatoly Rugalev <anatoliy.rugalev@gs-labs.ru>
 */
interface PayloadContract extends Arrayable
{

    /**
     * Sets value to payload array. Can be chained
     * @param string $key
     * @param mixed $value
     * @return PayloadContract
     */
    public function set($key, $value): PayloadContract;

    /**
     * Sets value to payload via `set()` method. Can be chained
     * @param $key
     * @param $value
     * @return PayloadContract
     */
    public function withValue($key, $value): PayloadContract;

    /**
     * Adds value to payload array. Can be chained
     * @param string $key
     * @param mixed $value
     * @return PayloadContract
     */
    public function add($key, $value): PayloadContract;

    /**
     * Returns value from payload
     * @param $key
     * @return mixed
     */
    public function get($key): mixed;

}
