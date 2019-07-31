<?php

namespace BotTemplateFramework\Builder\Blocks;


class RandomBlock extends Block {

    protected $rules = [];

    public function __construct($name = null) {
        parent::__construct('block', $name);
    }

    /**
     * @param $p
     * @param Block $block
     * @return $this
     */
    public function rule($p, Block $block) {
        $this->rules[] = [$p, $block->getName()];
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