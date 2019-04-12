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
use BotMan\Drivers\Facebook\Extensions\ButtonTemplate;
use BotMan\Drivers\Facebook\Extensions\Element;
use BotMan\Drivers\Facebook\Extensions\ElementButton;
use BotMan\Drivers\Facebook\Extensions\ListTemplate;
use BotMan\Drivers\Facebook\Extensions\QuickReplyButton;

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

    public function sendImage($imageUrl, $text = null, $options = null) {
        if ($text) {
            $this->sendMenuAndImage($imageUrl, $text, []);
        } else {
            $message = OutgoingMessage::create()->withAttachment(new Image($imageUrl));
            $this->reply($message);
        }
    }

    public function sendMenu($text, array $markup, $options = null) {
        foreach ($markup as $submenu) {
            $menu = ButtonTemplate::create($text)->addButtons($this->buildButtons($submenu));
            $this->reply($menu);
        }
    }

    public function sendMenuAndImage($imageUrl, $text, array $markup, $options = null) {
        $attachment = new Image($imageUrl, [
            'custom_payload' => true,
        ]);

        $message = OutgoingMessage::create($text)->withAttachment($attachment);

        $this->reply($message);
        $this->sendMenu('', $markup);
    }

    public function sendList(array $elements, array $globalButton = null, $options = null) {
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

    public function sendCarousel(array $elements, $options = null) {
        $this->sendList($elements);
    }

    public function sendText($text, $options = null) {
        $this->reply($text);
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
        $this->reply(Question::create($text)->addAction(QuickReplyButton::create()->type('location')));
    }

    public function requireLocationPayload($text, $options = null) {
        return Question::create($text)->addAction(QuickReplyButton::create()->type('location'));
    }

    public function requirePhonePayload($text, $options = null) {
        return Question::create($text)->addAction(QuickReplyButton::create()->type('user_phone_number'));
    }

    public function requireEmailPayload($text, $options = null) {
        return Question::create($text)->addAction(QuickReplyButton::create()->type('user_email'));
    }

    public function sendQuickButtons($text, array $markup, $options = null) {
        $question = new Question($text);
        foreach($markup as $submenu) {
            foreach($submenu as $callback=>$title) {
                $question->addButton((new Button($title))->value($callback));
            }
        }
        $this->reply($question);
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