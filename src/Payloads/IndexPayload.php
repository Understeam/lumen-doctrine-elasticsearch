<?php

namespace Understeam\LumenDoctrineElasticsearch\Payloads;

class IndexPayload extends RawPayload
{
    /**
     * @var string
     */
    protected $name;

    /**
     * IndexPayload constructor.
     * @param $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getData()
    {
        return array_merge_recursive(parent::getData(), [
            'index' => $this->name,
        ]);
    }
}
