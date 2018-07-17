<?php

namespace BotTemplateFramework\Distinct\Viber;


class ViberCarouselTemplate implements \JsonSerializable {

    protected $items = [];

    public function __construct(array $elements) {
        foreach ($elements as $element) {
            $actionType = 'reply';
            $actionBody = '';
            $actionTitle = '';
            foreach ($element['buttons'] as $callback => $title) {
                if (filter_var($callback, FILTER_VALIDATE_URL)) {
                    $actionType = "open-url";
                }
                $actionBody = $callback;
                $actionTitle = $title;
            }

            $image = [
                "Columns" => 6,
                "Rows" => 3,
                "ActionType" => $actionType,
                "ActionBody" => $actionBody,
                "Image" => $element['url']
            ];
            $text = [
                "Columns" => 6,
                "Rows" => 2,
                "TextSize" => "medium",
                "TextVAlign" => "middle",
                "TextHAlign" => "left",
                "ActionType" => $actionType,
                "ActionBody" => $actionBody,
                "Text" => "<font color=#323232><b>" . $element['title'] . "</b></font><font color=#777777><br>" . $element['description'] . "</font>",
            ];
            $button = [
                "Columns" => 6,
                "Rows" => 1,
                "ActionType" => $actionType,
                "ActionBody" => $actionBody,
                "Text" => "<font color=#ffffff>" . $actionTitle . "</font>",
                "TextSize" => "large",
                "TextVAlign" => "middle",
                "TextHAlign" => "middle",
            ];
            $this->items[] = $image;
            $this->items[] = $text;
            $this->items[] = $button;
        }
    }

    function jsonSerialize() {
        return [
            "min_api_version" => 2,
            "type" => "rich_media",
            "rich_media" => [
                "Type" => "rich_media",
                "ButtonsGroupColumns" => 6,
                "ButtonsGroupRows" => 6,
                "Buttons" => $this->items,
                "BgColor" => "#FFFFFF"
            ]
        ];
    }


}