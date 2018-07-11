<?php

namespace BotTemplateFramework\Builder\Blocks;


use BotTemplateFramework\Builder\Button;

class ImageBlock extends Block {

    protected $url;

    protected $buttons;

    protected $text;

    public function __construct($name = null) {
        parent::__construct('image', $name);
    }

    public function url($url) {
        $this->url = $url;
        return $this;
    }

    public function text($text) {
        $this->text = $text;
        return $this;
    }

    /**
     * @param Button[] $buttons
     * @return ImageBlock
     */
    public function buttons($buttons) {
        $this->buttons = $buttons;
        return $this;
    }

    public function toArray() {
        $array = parent::toArray();

        $content = [
            'url' => $this->url
        ];

        if ($this->text) {
            $content['text'] = $this->text;
        }

        if ($this->buttons) {
            $content['buttons'] = [];
            foreach ($this->buttons as $button) {
                /** @var Button $button */
                $content['buttons'][$button->getCallback()] = $button->getTitle();
            }
        }

        $array['content'] = $content;

        return $array;
    }

}