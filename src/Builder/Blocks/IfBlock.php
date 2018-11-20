<?php

namespace BotTemplateFramework\Builder\Blocks;


class IfBlock extends Block {

    protected $next = [];

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
    public function next($arg1, $operator, $arg2, Block $block) {
        $this->next[] = [$arg1, $operator, $arg2, $block->getName()];
        return $this;
    }

    public function toArray() {
        return array_merge(parent::toArray(), [
            'next' => $this->next
        ]);
    }

}