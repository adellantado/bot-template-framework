<?php

namespace BotTemplateFramework\Strategies;


use BotMan\BotMan\BotMan;
use BotMan\BotMan\Messages\Attachments\Audio;
use BotMan\BotMan\Messages\Attachments\File;
use BotMan\BotMan\Messages\Attachments\Image;
use BotMan\BotMan\Messages\Attachments\Video;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\Drivers\Facebook\Extensions\ButtonTemplate;
use BotMan\Drivers\Facebook\Extensions\Element;
use BotMan\Drivers\Facebook\Extensions\ElementButton;
use BotMan\Drivers\Facebook\Extensions\GenericTemplate;
use BotMan\Drivers\Facebook\Extensions\ListTemplate;
use BotMan\Drivers\Facebook\Extensions\QuickReplyButton;
use Mockery\CountValidator\Exception;

class FacebookComponentsStrategy implements IComponentsStrategy, IStrategy {
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
            if (count($submenu) > 3) {
                throw new Exception('Too many elements');
            }
            $menu = ButtonTemplate::create($text)->addButtons($this->buildButtons($submenu));
            $this->reply($menu);
        }
    }

    public function sendMenuAndImage($imageUrl, $text, array $markup) {
        if (count($markup) > 3) {
            throw new Exception('Too many elements');
        }
        $this->reply(GenericTemplate::create()->addImageAspectRatio(GenericTemplate::RATIO_SQUARE)->addElement(Element::create($text)->subtitle('')->image($imageUrl)->addButtons($this->buildButtons($markup))));
    }

    public function sendText($text) {
        $this->reply($text);
    }

    public function sendCarousel(array $elements) {
        if (count($elements) > 10) {
            throw new Exception('Facebook Generic Template component must include up to 10 elements');
        }
        $template = GenericTemplate::create()->addImageAspectRatio(GenericTemplate::RATIO_SQUARE);

        foreach ($elements as $item) {
            $element = Element::create($item['title'])->subtitle($item['description'])->image($item['url'])->addButtons($this->buildButtons($item['buttons']));
            $template->addElement($element);
        }
        $this->reply($template);
    }

    public function sendList(array $elements, array $globalButton = null) {
        if (count($elements) < 2 || count($elements) > 4) {
            throw new Exception('Facebook List component must include 2-4 elements');
        }
        $list = ListTemplate::create()->useCompactView();
        if ($globalButton) {
            $list->addGlobalButton($this->buildButtons($globalButton)[0]);
        }

        foreach ($elements as $item) {
            $element = Element::create($item['title'])->subtitle($item['description'])->image($item['url'])->addButtons($this->buildButtons($item['buttons']));
            $list->addElement($element);
        }
        $this->reply($list);
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
        $this->reply(Question::create($text)->addAction(QuickReplyButton::create()->type('location')));
    }

    public function requirePhone($text) {
        $this->reply(Question::create($text)->addAction(QuickReplyButton::create()->type('user_phone_number')));
    }

    public function requireEmail($text) {
        $this->reply(Question::create($text)->addAction(QuickReplyButton::create()->type('user_email')));
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