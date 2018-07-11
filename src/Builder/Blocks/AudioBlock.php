<?php

namespace BotTemplateFramework\Builder\Blocks;


class AudioBlock extends FileBlock {

    public function __construct($name = null) {
        parent::__construct($name);
        $this->type = 'audio';
    }

}