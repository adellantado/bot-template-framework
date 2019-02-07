<?php

namespace BotTemplateFramework\Strategies;


use BotMan\BotMan\BotMan;
use BotMan\BotMan\Messages\Attachments\Audio;
use BotMan\BotMan\Messages\Attachments\File;
use BotMan\BotMan\Messages\Attachments\Image;
use BotMan\BotMan\Messages\Attachments\Video;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;

class SkypeComponentsStrategy implements IComponentsStrategy, IStrategy {
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
        if ($text) {
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
        } else {
            $message = OutgoingMessage::create()->withAttachment(new Image($imageUrl));
            $this->reply($message);
        }
    }

    public function sendMenu($text, array $markup, $options = null) {
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

    public function sendMenuAndImage($imageUrl, $text, array $markup, $options = null) {
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

    public function sendList(array $elements, array $globalButton = null, $options = null) {
        $attachments = [];
        foreach ($elements as $element) {
            $attachment = [
                "contentType" => "application/vnd.microsoft.card.hero",
                "content" => [
                    "title" => $element['title'],
                    "images" => [
                        [
                            "url" => $element['url']
                        ]
                    ],
                    "buttons" => []
                ]
            ];
            if (array_key_exists('description', $element)) {
                $attachment['content']['text'] = $element['description'];
            }

            if (array_key_exists('buttons', $element)) {
                foreach ($element['buttons'] as $submenu) {
                    $attachment["content"]["buttons"] = array_merge($this->buildButtons($submenu),
                        $attachment["content"]["buttons"]);
                }
            }
            $attachments[] = $attachment;
        }

        $this->bot->reply("", [
            "attachments" => $attachments,
            "attachmentLayout" => 'list'
        ]);
    }

    public function sendCarousel(array $elements, $options = null) {
        $attachments = [];
        foreach ($elements as $element) {
            $attachment = [
                "contentType" => "application/vnd.microsoft.card.hero",
                "content" => [
                    "title" => $element['title'],
                    "images" => [
                        [
                            "url" => $element['url']
                        ]
                    ],
                    "buttons" => []
                ]
            ];
            if (array_key_exists('description', $element)) {
                $attachment['content']['text'] = $element['description'];
            }

            if (array_key_exists('buttons', $element)) {
                foreach ($element['buttons'] as $submenu) {
                    $attachment["content"]["buttons"] = array_merge($this->buildButtons($submenu),
                        $attachment["content"]["buttons"]);
                }
            }
            $attachments[] = $attachment;
        }

        $this->bot->reply("", [
            "attachments" => $attachments,
            "attachmentLayout" => 'carousel'
        ]);
    }

    public function sendQuickButtons($text, array $markup) {
        // TODO: Implement sendQuickButtons() method.
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

    public function requireLocation($text, $options = null) {
        // TODO: Implement sendLocation() method.
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

    public function requirePhonePayload($text, $options = null) {
        // TODO: Implement requirePhonePayload() method.
        return null;
    }

    public function requireEmailPayload($text, $options = null) {
        // TODO: Implement requireEmailPayload() method.
        return null;
    }


}