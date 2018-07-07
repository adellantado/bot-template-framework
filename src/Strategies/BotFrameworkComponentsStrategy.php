<?php

namespace BotTemplateFramework\Strategies;


use BotMan\BotMan\BotMan;
use BotMan\BotMan\Messages\Attachments\Audio;
use BotMan\BotMan\Messages\Attachments\File;
use BotMan\BotMan\Messages\Attachments\Video;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;

class BotFrameworkComponentsStrategy implements IComponentsStrategy, IStrategy {
    protected $bot;

    public function __construct(BotMan $bot) {
        $this->bot = $bot;
    }

    public function getBot() {
        return $this->bot;
    }

    public function reply($message, $additionalParameters = []) {
        $this->bot->reply($message, $additionalParameters);
    }

    public function sendImage($imageUrl, $text = null) {
        // TODO what if text == null
        $menu = [
            "title" => $text,
            "images" => [
                [
                    "url" => $imageUrl
                ]
            ],
            "buttons" => []
        ];

        $this->bot->reply('', [
            "attachments" => [
                [
                    "contentType" => "application/vnd.microsoft.card.hero",
                    "content" => $menu
                ]
            ]
        ]);
    }

    public function sendMenu($text, array $markup) {
        $menu = [
            "title" => $text,
            "images" => [],
            "buttons" => []
        ];
        foreach ($markup as $submenu) {
            $menu["buttons"] = array_merge($this->buildButtons($submenu), $menu["buttons"]);
        }

        $this->bot->reply('', [
            "attachments" => [
                [
                    "contentType" => "application/vnd.microsoft.card.hero",
                    "content" => $menu
                ]
            ]
        ]);
    }

    public function sendMenuAndImage($imageUrl, $text, array $markup) {
        $menu = [
            "title" => $text,
            "images" => [
                [
                    "url" => $imageUrl
                ]
            ],
            "buttons" => []
        ];
        foreach ($markup as $submenu) {
            $menu["buttons"] = array_merge($this->buildButtons($submenu), $menu["buttons"]);
        }

        $this->bot->reply('', [
            "attachments" => [
                [
                    "contentType" => "application/vnd.microsoft.card.hero",
                    "content" => $menu
                ]
            ]
        ]);
    }

    public function sendText($text) {
        $this->reply($text);
    }

    public function sendList(array $elements, array $globalButton = null) {
        $attachments = [];
        foreach ($elements as $element) {
            $attachment = [
                "contentType" => "application/vnd.microsoft.card.hero",
                "content" => [
                    "title" => $element['title'],
                    "text" => $element['description'],
                    "images" => [
                        [
                            "url" => $element['url']
                        ]
                    ],
                    "buttons" => []
                ]
            ];
            foreach ($element['buttons'] as $submenu) {
                $attachment["content"]["buttons"] = array_merge($this->buildButtons($submenu),
                    $attachment["content"]["buttons"]);
            }
            $attachments[] = $attachment;
        }

        $this->bot->reply("", [
            "attachments" => $attachments,
            "attachmentLayout" => 'list'
        ]);
    }

    public function sendCarousel(array $elements) {
        $attachments = [];
        foreach ($elements as $element) {
            $attachment = [
                "contentType" => "application/vnd.microsoft.card.hero",
                "content" => [
                    "title" => $element['title'],
                    "text" => $element['description'],
                    "images" => [
                        [
                            "url" => $element['url']
                        ]
                    ],
                    "buttons" => []
                ]
            ];
            foreach ($element['buttons'] as $submenu) {
                $attachment["content"]["buttons"] = array_merge($this->buildButtons($submenu),
                    $attachment["content"]["buttons"]);
            }
            $attachments[] = $attachment;
        }

        $this->bot->reply("", [
            "attachments" => $attachments,
            "attachmentLayout" => 'carousel'
        ]);
    }

    public function sendAudio($url, $text = null) {
        $this->reply(OutgoingMessage::create($text, new Audio($url)));
    }

    public function sendVideo($url, $text = null) {
        $this->reply(OutgoingMessage::create($text, new Video($url)));
    }

    public function sendFile($url, $text = null) {
        $this->reply(OutgoingMessage::create($text, new File($url)));
    }

    public function sendLocation() {
        // TODO: Implement sendLocation() method.
    }

    public function sendPhone() {
        // TODO: Implement sendPhone() method.
    }

    /**
     * @param array $markup
     * @return array
     */
    protected function buildButtons(array $markup) {
        $buttons = [];
        foreach ($markup as $callback => $title) {
            if (in_array(parse_url($callback, PHP_URL_SCHEME), ['mailto', 'http', 'https', 'tel'])) {
                $buttons[] = [
                    "type" => "openUrl",
                    "title" => $title,
                    "value" => $callback
                ];
                continue;
            }
            $buttons[] = [
                "type" => "postBack",
                "title" => $title,
                "value" => $callback
            ];
        }
        return $buttons;
    }

}