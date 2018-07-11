<?php

namespace BotTemplateFramework\Builder\Blocks;


use BotTemplateFramework\Builder\Items\ListItem;

class ListBlock extends Block {
    protected $items;

    public function __construct($name = null) {
        parent::__construct('list', $name);
    }

    /**
     * @param ListItem[] $items
     * @return ListBlock
     */
    public function items($items) {
        $this->items = $items;
        return $this;
    }

    public function toArray() {
        $array = parent::toArray();

        $array['content'] = array_map(function (ListItem $item) {
            return $item->toArray();
        }, $this->items);

        return $array;
    }

}