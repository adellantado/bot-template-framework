<?php

namespace BotTemplateFramework\Strategies;

use \BotMan\BotMan\BotMan;

abstract class Strategy implements IStrategy, IComponentsStrategy {
    /** @var BotMan IComponentsStrategy */
    protected $bot;
    /** @var  IComponentsStrategy */
    protected $componentsStrategy;

    public function __construct(BotMan $bot) {
        $this->bot = $bot;
    }

    /**
     * @return BotMan
     */
    public function getBot() {
        return $this->bot;
    }

    public function setComponentsStrategy(IComponentsStrategy $componentsStrategy) {
        $this->componentsStrategy = $componentsStrategy;
        return $this;
    }

    /**
     * @param $message
     * @param array $additionalParameters
     * @return mixed
     */
    public function reply($message, $additionalParameters = []) {
        return $this->bot->reply($message, $additionalParameters);
    }

    //-------------Components

    public function sendImage($imageUrl, $text = null, $options = null) {
        $this->componentsStrategy->sendImage($imageUrl, $text, $options);
    }

    public function sendMenu($text, array $markup, $options = null) {
        $this->componentsStrategy->sendMenu($text, $markup, $options);
    }

    public function sendMenuAndImage($imageUrl, $text, array $markup, $options = null) {
        $this->componentsStrategy->sendMenuAndImage($imageUrl, $text, $markup, $options);
    }

    public function sendText($text, $options = null) {
        $this->componentsStrategy->sendText($text, $options);
    }

    public function sendList(array $elements, array $globalButton = null, $options = null) {
        $this->componentsStrategy->sendList($elements, $globalButton, $options);
    }

    public function sendCarousel(array $elements, $options = null) {
        $this->componentsStrategy->sendCarousel($elements, $options);
    }

    public function sendQuickButtons($text, array $markup, $options = null) {
        $this->componentsStrategy->sendQuickButtons($text, $markup, $options);
    }

    public function sendAudio($url, $text = null, $options = null) {
        $this->componentsStrategy->sendAudio($url, $text, $options);
    }

    public function sendVideo($url, $text = null, $options = null) {
        $this->componentsStrategy->sendVideo($url, $text, $options);
    }

    public function sendFile($url, $text = null, $options = null) {
        $this->componentsStrategy->sendFile($url, $text, $options);
    }

    public function sendPayload($payload) {
        $this->componentsStrategy->sendPayload($payload);
    }

    public function requireLocation($text, $options = null) {
        $this->componentsStrategy->requireLocation($text, $options);
    }

    public function requireLocationPayload($text, $options = null) {
        return $this->componentsStrategy->requireLocationPayload($text, $options);
    }

    public function requirePhonePayload($text, $options = null) {
        return $this->componentsStrategy->requirePhonePayload($text, $options);
    }

    public function requireEmailPayload($text, $options = null) {
        return $this->componentsStrategy->requireEmailPayload($text, $options);
    }


    /**
     * This method needed for Telegram Carousel component. When override Telegram Strategy with custom one,
     * this method should be present in there.
     *
     * @param BotMan $bot
     * @param $messageId
     * @param $element
     */
    public function carouselSwitch(BotMan $bot, $messageId, $element) {
        if ($this->bot->getDriver() instanceof \BotMan\Drivers\Telegram\TelegramDriver) {
            $this->componentsStrategy->carouselSwitch($bot, $messageId, $element);
        }
    }

}