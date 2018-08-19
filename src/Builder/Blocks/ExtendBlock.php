<?php

namespace BotTemplateFramework\Builder\Blocks;


class ExtendBlock extends Block {

    /**
     * @var Block
     */
    protected $base;

    public function __construct($name = null) {
        parent::__construct('extend', $name);
    }

    /**
     * @param Block $block
     * @return $this
     */
    public function base($block) {
        $this->base = $block;
        return $this;
    }

    public function toArray() {
        return array_merge(parent::toArray(), [
            'base' => $this->base->name
        ]);
    }

}