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
        return $this->bot->reply($message, $additionalParameters);
    }

    public function sendImage($imageUrl, $text = null, $options = null) {
        if ($text) {
            $this->reply(new PictureTemplate($imageUrl, $text));
        } else {
            $message = OutgoingMessage::create()->withAttachment(new Image($imageUrl));
            $this->reply($message);
        }
    }

    public function sendMenu($text, array $markup, $options = null) {
        $menu = new KeyboardTemplate($text, $options['DefaultHeight'] ?? false);
        $this->buildMenu($markup, $menu, $options);
        $this->reply($menu);
    }

    public function sendMenuAndImage($imageUrl, $text, array $markup, $options = null) {
        $menu = new MenuTemplate($text, $imageUrl, $options['DefaultHeight'] ?? false);
        $this->buildMenu([$markup], $menu, $options);

        $this->reply($menu);
    }

    public function sendText($text, $options = null) {
        $this->reply($text);
    }

    public function sendList(array $elements, array $globalButton = null, $options = null) {
        foreach ($elements as $item) {
            $this->sendMenuAndImage($item['url'], $item['title'], $item['buttons'], $options);
        }

        if ($globalButton) {
            $this->sendMenu('', $globalButton, $options);
        }
    }

    public function sendCarousel(array $elements, $options = null) {
        $this->reply(new CarouselTemplate($elements));
    }

    public function sendQuickButtons($text, array $markup, $options = null) {
        $this->sendMenu($text, $markup, $options);
    }

    public function sendAudio($url, $text = null, $options = null) {
        $this->reply(OutgoingMessage::create($text, new Audio($url)));
    }

    public function sendVideo($url, $text = null, $options = null) {
        $this->reply(OutgoingMessage::create($text, new Video($url)));
    }

    public function sendFile($url, $text = null, $options = null) {
        $this->reply(OutgoingMessage::create($text, new File($url)));
    }

    public function requireLocation($text, $options = null) {
        return $this->reply($text);
    }

    public function requireLocationPayload($text, $options = null) {
        return null;
    }

    public function requirePhonePayload($text, $options = null) {
        return null;
    }

    public function requireEmailPayload($text, $options = null) {
        return null;
    }

    protected function buildMenu(array $markup, $keyboard, $options = null) {
        foreach ($markup as $submenu) {
            foreach ($submenu as $callback => $title) {
                if (in_array(parse_url($callback, PHP_URL_SCHEME), ['mailto', 'http', 'https', 'tel'])) {
                    $keyboard->addButton($title, 'open-url', $callback, $options['TextSize'] ?? 'regular', $options['BgColor'] ?? null);
                    continue;
                }
                $keyboard->addButton($title, 'reply', $callback, $options['TextSize'] ?? 'regular', $options['BgColor'] ?? null);
            }
        }

        return $keyboard;
    }
}