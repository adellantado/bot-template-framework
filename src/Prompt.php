<?php

namespace BotTemplateFramework;


use BotTemplateFramework\Blocks\Block;

class Prompt {
    protected $text;
    protected $nextBlock;

    public function __construct(string $text, Block $nextBlock) {
        $this->text = $text;
        $this->nextBlock = $nextBlock;
    }

    public function getText() {
        return $this->text;
    }

    public function getNextBlock() {
        return $this->nextBlock;
    }

}