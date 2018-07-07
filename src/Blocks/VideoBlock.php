<?php

namespace BotTemplateFramework\Blocks;


class VideoBlock extends FileBlock {

    public function __construct($name = null) {
        parent::__construct($name);
        $this->type = 'video';
    }

}