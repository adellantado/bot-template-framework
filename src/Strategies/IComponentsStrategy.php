<?php

namespace BotTemplateFramework\Strategies;


interface IComponentsStrategy {

    public function sendImage($imageUrl, $text = null);

    public function sendMenu($text, array $markup);

    public function sendMenuAndImage($imageUrl, $text, array $markup);

    public function sendText($text);

    public function sendList(array $elements, array $globalButton = null);

    public function sendCarousel(array $elements);

    public function sendQuickButtons($text, array $markup);

    public function sendAudio($url, $text = null);

    public function sendVideo($url, $text = null);

    public function sendFile($url, $text = null);

    public function requireLocation($text);

    public function requirePhonePayload($text);

    public function requireEmailPayload($text);

}