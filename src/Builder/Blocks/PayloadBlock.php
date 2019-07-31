<?php


namespace BotTemplateFramework\Builder\Blocks;


class PayloadBlock extends Block {

    protected $payload;

    public function __construct($name = null) {
        parent::__construct('payload', $name);
    }

    /**
     * @param $payload
     * @return $this
     */
    public function payload($payload) {
        $this->payload = $payload;
        return $this;
    }

    public function toArray() {
        return array_merge(parent::toArray(), [
            'payload' => $this->payload
        ]);
    }

}