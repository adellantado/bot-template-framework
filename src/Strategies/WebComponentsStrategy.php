<?php

namespace BotTemplateFramework\Strategies;


use BotMan\BotMan\BotMan;
use BotMan\BotMan\Messages\Attachments\Image;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;
use BotMan\Drivers\Facebook\Extensions\ButtonTemplate;
use BotMan\Drivers\Facebook\Extensions\Element;
use BotMan\Drivers\Facebook\Extensions\ElementButton;
use BotMan\Drivers\Facebook\Extensions\ListTemplate;

class WebComponentsStrategy implements IComponentsStrategy, IStrategy {
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
            $this->sendMenuAndImage($imageUrl, $text, []);
        } else {
            $message = OutgoingMessage::create()->withAttachment(new Image($imageUrl));
            $this->reply($message);
        }
    }

    public function sendMenu($text, array $markup) {
        foreach ($markup as $submenu) {
            $menu = ButtonTemplate::create($text)->addButtons($this->buildButtons($submenu));
            $this->reply($menu);
        }
    }

    public function sendMenuAndImage($imageUrl, $text, array $markup) {
        $attachment = new Image($imageUrl, [
            'custom_payload' => true,
        ]);

        $message = OutgoingMessage::create($text)->withAttachment($attachment);

        $this->reply($message);
        $this->sendMenu('', $markup);
    }

    public function sendList(array $elements, array $globalButton = null) {
//        $list = ListTemplate::create()
//            ->useCompactView();
//        if ($globalButton) {
//            $list->addGlobalButton($this->buildButtons($globalButton[0])[0]);
//        }
//
//        foreach ($elements as $item) {
//            $element = Element::create($item['title'])
////                ->subtitle($item['description'])
//                ->image($item['url']);
//            foreach($item['buttons'] as $submenu) {
//                $element->addButtons($this->buildButtons($submenu));
//            }
//            $list->addElement($element);
//        }
//
//        $this->reply($list);

        foreach ($elements as $item) {
            $this->sendMenuAndImage($item['url'], $item['title'], [$item['buttons']]);
        }

        if ($globalButton) {
            $this->sendMenu('', $globalButton);
        }
    }

    public function sendCarousel(array $elements) {
        $this->sendList($elements);
    }

    public function sendText($text) {
        $this->reply($text);
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
                $buttons[] = ElementButton::create($title)->type(ElementButton::TYPE_WEB_URL)->url($callback);
                continue;
            }
            $buttons[] = ElementButton::create($title)->type('postback')->payload($callback);
        }
        return $buttons;
    }

}