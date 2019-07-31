<?php

namespace BotTemplateFramework\Strategies;


interface IComponentsStrategy {

    public function sendImage($imageUrl, $text = null, $options = null);

    public function sendMenu($text, array $markup, $options = null);

    public function sendMenuAndImage($imageUrl, $text, array $markup, $options = null);

    public function sendText($text, $options = null);

    public function sendList(array $elements, array $globalButton = null, $options = null);

    public function sendCarousel(array $elements, $options = null);

    public function sendQuickButtons($text, array $markup, $options = null);

    public function sendAudio($url, $text = null, $options = null);

    public function sendVideo($url, $text = null, $options = null);

    public function sendFile($url, $text = null, $options = null);

    public function sendPayload($payload);

    public function requireLocation($text, $options = null);

    public function requireLocationPayload($text, $options = null);

    public function requirePhonePayload($text, $options = null);

    public function requireEmailPayload($text, $options = null);

}