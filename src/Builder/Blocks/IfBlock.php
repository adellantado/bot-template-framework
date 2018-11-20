<?php

namespace BotTemplateFramework\Builder\Blocks;


class IfBlock extends Block {

    protected $rules = [];

    public function __construct($name = null) {
        parent::__construct('if', $name);
    }

    /**
     * @param $arg1
     * @param $operator
     * @param $arg2
     * @param Block $block
     * @return $this
     */
    public function rule($arg1, $operator, $arg2, Block $block) {
        $this->rules[] = [$arg1, $operator, $arg2, $block->getName()];
        return $this;
    }

    public function next($next) {
        throw new \Exception("call 'next' method is not allowable, use 'rule' method instead");
    }

    public function toArray() {
        $array = parent::toArray();
        $array['next'] = $this->rules;
        return $array;
    }

}