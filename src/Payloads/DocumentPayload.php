<?php

namespace Understeam\LumenDoctrineElasticsearch\Payloads;

class DocumentPayload extends RawPayload
{
    /**
     * @var TypePayload
     */
    protected $type;

    /**
     * @var string
     */
    protected $id;

    /**
     * DocumentPayload constructor.
     * @param TypePayload $type
     * @param $id
     */
    public function __construct(TypePayload $type, $id)
    {
        $this->type = $type;
        $this->id = $id;
    }

    public function getData()
    {
        return array_merge_recursive(parent::getData(), $this->type->getData(), [
            'id' => $this->id
        ]);
    }
}
