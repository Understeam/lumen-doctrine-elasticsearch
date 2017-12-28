<?php

namespace Understeam\LumenDoctrineElasticsearch\Payloads;

class TypePayload extends RawPayload
{
    /**
     * @var IndexPayload
     */
    protected $index;

    /**
     * @var string
     */
    protected $name;

    /**
     * TypePayload constructor.
     * @param IndexPayload $index
     * @param $name
     */
    public function __construct(IndexPayload $index, $name)
    {
        $this->index = $index;
        $this->name = $name;
    }

    public function getData()
    {
        return array_merge_recursive(parent::getData(), $this->index->getData(), [
            'type' => $this->name
        ]);
    }
}
