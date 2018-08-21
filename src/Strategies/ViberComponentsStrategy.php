<?php

namespace BotTemplateFramework\Strategies;


use BotMan\BotMan\BotMan;
use BotMan\BotMan\Messages\Attachments\Audio;
use BotMan\BotMan\Messages\Attachments\File;
use BotMan\BotMan\Messages\Attachments\Image;
use BotMan\BotMan\Messages\Attachments\Video;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;
use BotMan\BotMan\Messages\Outgoing\Question;
use TheArdent\Drivers\Viber\Extensions\CarouselTemplate;
use TheArdent\Drivers\Viber\Extensions\KeyboardTemplate;
use TheArdent\Drivers\Viber\Extensions\MenuTemplate;
use TheArdent\Drivers\Viber\Extensions\PictureTemplate;

class ViberComponentsStrategy implements IComponentsStrategy, IStrategy {
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
            $this->reply(new PictureTemplate($imageUrl, $text));
        } else {
            $message = OutgoingMessage::create()->withAttachment(new Image($imageUrl));
            $this->reply($message);
        }
    }

    public function sendMenu($text, array $markup) {
        $menu = new KeyboardTemplate($text);
        $this->buildMenu($markup, $menu);
        $this->reply($menu);
    }

    public function sendMenuAndImage($imageUrl, $text, array $markup) {
        $menu = new MenuTemplate($text, $imageUrl);
        $this->buildMenu($markup, $menu);

        $this->reply($menu);
    }

    public function sendText($text) {
        $this->reply($text);
    }

    public function sendList(array $elements, array $globalButton = null) {
        foreach ($elements as $item) {
            $this->sendMenuAndImage($item['url'], $item['title'], $item['buttons']);
        }

        if ($globalButton) {
            $this->sendMenu('', $globalButton);
        }
    }

    public function sendCarousel(array $elements) {
        $this->reply(new CarouselTemplate($elements));
    }

    public function sendQuickButtons($text, array $markup) {
        $question = new Question($text);
        foreach($markup as $callback=>$title) {
            $question->addButton((new Button($title))->value($callback));
        }
        $this->reply($question);
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

    public function requireLocation($text) {
        // TODO: Implement sendLocation() method.
    }

    protected function buildMenu(array $markup, $keyboard) {
        foreach ($markup as $submenu) {
            foreach ($submenu as $callback => $title) {
                if (in_array(parse_url($callback, PHP_URL_SCHEME), ['mailto', 'http', 'https', 'tel'])) {
                    $keyboard->addButton($title, 'open-url', $callback);
                    continue;
                }
                $keyboard->addButton($title, 'reply', $callback);
            }
        }

        return $keyboard;
    }
}