<?php

namespace BotTemplateFramework\Blocks;


class FileBlock extends Block
{

    protected $url;

    protected $text;

    public function __construct($name = null)
    {
        parent::__construct('file', $name);
    }

    public function url($url) {
        $this->url = $url;
        return $this;
    }

    public function text($text) {
        $this->text = $text;
        return $this;
    }

    public function toArray()
    {
        $array = parent::toArray();

        $content = [
            'url' => $this->url
        ];

        if ($this->text) {
            $content['text'] = $this->text;
        }

        $array['content'] = $content;

        return $array;
    }

}