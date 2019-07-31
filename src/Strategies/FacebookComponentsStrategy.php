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
use BotMan\Drivers\Facebook\Extensions\GenericTemplate;
use BotMan\Drivers\Facebook\Extensions\ListTemplate;
use BotMan\Drivers\Facebook\Extensions\QuickReplyButton;
use BotMan\Drivers\Facebook\FacebookDriver;
use Exception;

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

    public function sendImage($imageUrl, $text = null, $options = null) {
        if ($text) {
            $this->sendMenuAndImage($imageUrl, $text, [], $options);
        } else {
            $message = OutgoingMessage::create()->withAttachment(new Image($imageUrl));
            $this->reply($message);
        }
    }

    public function sendMenu($text, array $markup, $options = null) {
        foreach ($markup as $submenu) {
            if (count($submenu) > 3) {
                throw new Exception('Too many elements');
            }
            $menu = ButtonTemplate::create($text)->addButtons($this->buildButtons($submenu));
            $this->reply($menu);
        }
    }

    public function sendMenuAndImage($imageUrl, $text, array $markup, $options = null) {
        if (count($markup) > 3) {
            throw new Exception('Too many elements');
        }
        $this->reply(GenericTemplate::create()->addImageAspectRatio($options['image_aspect_ratio'] ?? GenericTemplate::RATIO_SQUARE)->addElement(Element::create($text)->subtitle($options['subtitle'] ?? '')->image($imageUrl)->addButtons($this->buildButtons($markup, $options))));
    }

    public function sendText($text, $options = null) {
        $this->reply($text);
    }

    public function sendCarousel(array $elements, $options = null) {
        if (count($elements) > 10) {
            throw new Exception('Facebook Generic Template component must include up to 10 elements');
        }
        $template = GenericTemplate::create()->addImageAspectRatio($options['image_aspect_ratio'] ?? GenericTemplate::RATIO_SQUARE);

        foreach ($elements as $item) {
            $element = Element::create($item['title'])->image($item['url']);
            if (array_key_exists('description', $item)) {
                $element->subtitle($item['description']);
            }
            if (array_key_exists('buttons', $item)) {
                $element->addButtons($this->buildButtons($item['buttons']));
            }
            $template->addElement($element);
        }
        $this->reply($template);
    }

    public function sendList(array $elements, array $globalButton = null, $options = null) {
        if (count($elements) < 2 || count($elements) > 4) {
            throw new Exception('Facebook List component must include 2-4 elements');
        }
        $list = ListTemplate::create()->useCompactView();
        if ($globalButton) {
            $list->addGlobalButton($this->buildButtons($globalButton)[0]);
        }

        foreach ($elements as $item) {
            $element = Element::create($item['title'])->image($item['url']);
            if (array_key_exists('description', $item)) {
                $element->subtitle($item['description']);
            }
            if (array_key_exists('buttons', $item)) {
                $element->addButtons($this->buildButtons($item['buttons']));
            }
            $list->addElement($element);
        }
        $this->reply($list);
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

    public function sendAudio($url, $text = null, $options = null) {
        $this->reply(OutgoingMessage::create($text, new Audio($url)));
    }

    public function sendVideo($url, $text = null, $options = null) {
        $this->reply(OutgoingMessage::create($text, new Video($url)));
    }

    public function sendFile($url, $text = null, $options = null) {
        $this->reply(OutgoingMessage::create($text, new File($url)));
    }

    public function sendPayload($payload){
        $payload = array_map(function($val){
            if (is_array($val)) {
                return json_encode($val);
            }
            return $val;
        }, $payload);
        $driverEvent = $this->bot->getDriver()->hasMatchingEvent();
        if ($driverEvent) {
            $data = $driverEvent->getPayload();
            if (isset($data['optin']) && isset($data['optin']['user_ref'])) {
                $recipient = ['user_ref' => $data['optin']['user_ref']];
            } else {
                $recipient = ['id' => $data['sender']['id']];
            }
        } else {
            $recipient = ['id' => $this->bot->getMessage()->getSender()];
        }

        $parameters = array_merge_recursive([
            'messaging_type' => FacebookDriver::TYPE_RESPONSE,
            'access_token' => $this->bot->getDriver()->getConfig()->get('token'),
            'recipient' => $recipient,
        ], $payload);

        $this->bot->sendPayload($parameters);
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

    /**
     * @param array $markup
     * @return array
     */
    protected function buildButtons(array $markup, $options = null) {
        $buttons = [];
        foreach ($markup as $callback => $title) {
            $schema = parse_url($callback, PHP_URL_SCHEME);
            if (in_array($schema, ['http', 'https', 'tel', 'share'])) {
                if ($schema == 'share') {
                    $buttons[] = ElementButton::create($title)->type(ElementButton::TYPE_SHARE);
                } elseif ($schema == 'tel') {
                    $buttons[] = ElementButton::create($title)->type(ElementButton::TYPE_CALL)->payload($callback);
                } else {
                    $buttons[] = ElementButton::create($title)->type(ElementButton::TYPE_WEB_URL)->url($callback);
                }
                continue;
            }
            $buttons[] = ElementButton::create($title)->type('postback')->payload($callback);
        }
        return $buttons;
    }

}