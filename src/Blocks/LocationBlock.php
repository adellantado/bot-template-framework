<?php

namespace BotTemplateFramework\Blocks;


class LocationBlock extends Block {

    /**
     * @var string
     */
    protected $text;

    public function __construct($name = null) {
        parent::__construct('location', $name);
    }

    /**
     * @param string $text
     * @return $this
     */
    public function text($text) {
        $this->text = $text;
        return $this;
    }

    public function toArray() {
        return array_merge(parent::toArray(), [
            'content' => $this->text
        ]);
    }

}