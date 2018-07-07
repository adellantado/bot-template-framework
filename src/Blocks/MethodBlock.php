<?php

namespace BotTemplateFramework\Blocks;


class MethodBlock extends Block {

    protected $method;

    public function __construct($name = null) {
        parent::__construct('method', $name);
    }

    public function method($method) {
        $this->method = $method;
        return $this;
    }

    public function toArray() {
        $array = parent::toArray();

        $array['method'] = $this->method;

        return $array;
    }

}