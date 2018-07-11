<?php

namespace BotTemplateFramework\Distinct\Viber;

use JsonSerializable;

class ViberMenuTemplate implements JsonSerializable {
    private $type = 'picture';

    /**
     * @var string
     */
    protected $text;

    /**
     * @var array
     */
    protected $buttons;

    protected $imageUrl;

    /**
     * PictureTemplate constructor.
     *
     * @param string $imageUrl
     * @param string $text
     */
    public function __construct($text, $imageUrl) {
        $this->text = $text;
        $this->imageUrl = $imageUrl;
    }

    /**
     * @return array
     */
    public function jsonSerialize() {
        return [
            'type' => $this->type,
            'text' => $this->text,
            'media' => $this->imageUrl,
            'keyboard' => [
                'Type' => 'keyboard',
                'DefaultHeight' => false,
                'Buttons' => $this->buttons
            ]
        ];
    }

    /**
     * @param        $text
     * @param string $actionType
     * @param string $actionBody
     * @param string $textSize
     *
     * @return ViberMenuTemplate
     */
    public function addButton($text, $actionType = 'reply', $actionBody = 'reply to me', $textSize = 'regular') {
        $this->buttons[] = [
            "ActionType" => $actionType,
            "ActionBody" => $actionBody,
            "Text" => $text,
            "TextSize" => $textSize,
        ];
        return $this;
    }

}