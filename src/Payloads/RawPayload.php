<?php
declare(strict_types=1);

namespace Understeam\LumenDoctrineElasticsearch\Payloads;

/**
 * Class RawPayload
 *
 * @author Anatoly Rugalev <anatoliy.rugalev@gs-labs.ru>
 */
class RawPayload implements PayloadContract
{

    /**
     * @var array
     */
    protected $data = [];

    public function setData(array $data)
    {
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->getData();
    }

    public function set($key, $value): PayloadContract
    {
        if (!is_null($key)) {
            array_set($this->data, $key, $value);
        }

        return $this;
    }

    public function withValue($key, $value): PayloadContract
    {
        if (!empty($value)) {
            $this->set($key, $value);
        }

        return $this;
    }

    public function add($key, $value): PayloadContract
    {
        if (!empty($key)) {
            $currentValue = array_get($this->data, $key, []);

            if (!is_array($currentValue)) {
                $currentValue = array_wrap($currentValue);
            }

            $currentValue[] = $value;

            array_set($this->data, $key, $currentValue);
        }
        return $this;
    }

    public function get($key): mixed
    {
        return array_get($this->data, $key);
    }
}
