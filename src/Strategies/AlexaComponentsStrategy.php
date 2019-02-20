<?php

namespace BotTemplateFramework\Strategies;


use BotMan\BotMan\BotMan;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;
use BotMan\Drivers\AmazonAlexa\Extensions\Card;

class AlexaComponentsStrategy implements IComponentsStrategy, IStrategy {

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

    public function sendCard($title, $subtitle, $url, $description = null) {
        $card = Card::create($title, $subtitle)->type(Card::STANDARD_CARD_TYPE)->image($url);

        if ($description) {
            $card->text($description);
        }

        $message = OutgoingMessage::create('This is the spoken response')->withAttachment($card);
        $this->reply($message);
    }

    public function sendImage($imageUrl, $text = null) {
        $this->sendCard($text, '', $imageUrl);
    }

    public function sendMenu($text, array $markup, $options = null) {
        // TODO: Implement sendMenu() method.
    }

    public function sendMenuAndImage($imageUrl, $text, array $markup, $options = null) {
        // TODO: Implement sendMenuAndImage() method.
    }

    public function sendText($text) {
        $this->reply($text);
    }

    public function sendList(array $elements, array $globalButton = null, $options = null) {
        $subtitle = '';
        $description = null;
        if (array_key_exists('subtitle', $elements[0])) {
            $subtitle = $elements[0]['subtitle'];
        }
        if (array_key_exists('description', $elements[0])) {
            $description = $elements[0]['description'];
        }
        $this->sendCard($elements[0]['title'], $subtitle, $elements[0]['url'], $description);
    }

    public function sendCarousel(array $elements, $options = null) {
        $this->sendList($elements);
    }

    public function sendAudio($url, $text = null) {
        // TODO: Implement sendAudio() method.
    }

    public function sendVideo($url, $text = null) {
        // TODO: Implement sendVideo() method.
    }

    public function sendFile($url, $text = null) {
        // TODO: Implement sendFile() method.
    }

    public function requireLocation($text, $options = null) {
        // TODO: Implement requireLocation() method.
    }

    public function sendQuickButtons($text, array $markup) {
        // TODO: Implement sendQuickButtons() method.
    }

    public function requireLocationPayload($text, $options = null) {
        // TODO: Implement requireLocationPayload() method.
        return null;
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