<?php

namespace BotTemplateFramework\Builder;


class Button {

    protected $title;

    protected $callback;

    public function __construct($title) {
        $this->title = $title;
    }

    public function url($url) {
        $this->callback = $url;
        return $this;
    }

    public function callback($text) {
        $this->callback = $text;
        return $this;
    }

    public function getTitle() {
        return $this->title;
    }

    public function getCallback() {
        return $this->callback;
    }

}