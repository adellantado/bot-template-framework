<?php

namespace BotTemplateFramework\Items;

use BotTemplateFramework\Button;

class ListItem implements \JsonSerializable
{
    protected $title;

    protected $description;

    protected $url;

    protected $buttons;

    public function __construct($title, $url)
    {
        $this->title = $title;
        $this->url = $url;
    }

    public function url($url) {
        $this->url = $url;
        return $this;
    }

    public function description($text) {
        $this->description = $text;
        return $this;
    }

    /**
     * @param Button[] $buttons
     * @return ListItem
     */
    public function buttons($buttons) {
        $this->buttons = $buttons;
        return $this;
    }

    function jsonSerialize()
    {
        return $this->toArray();
    }

    public function toArray()
    {
        $content = [
            'url' => $this->url,
            'title' => $this->title
        ];

        if ($this->description) {
            $content['description'] = $this->description;
        }

        if ($this->buttons) {
            $content['buttons'] = [];
            foreach($this->buttons as $button) {
                /** @var Button $button */
                $content['buttons'][$button->getCallback()] = $button->getTitle();
            }
        }

        return $content;
    }

}