<?php

namespace BotTemplateFramework\Builder\Drivers;


class WebDriver extends Driver {

    protected $token;

    public function __construct($token = 'web') {
        parent::__construct('Web');
        $this->token = $token;
    }

    public function toArray() {
        return array_merge(parent::toArray(), [
            'token' => $this->token
        ]);
    }

}