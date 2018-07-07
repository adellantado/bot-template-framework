<?php

namespace BotTemplateFramework\Drivers;


class SlackDriver extends Driver {
    protected $token;

    public function __construct($token) {
        parent::__construct('Slack');
        $this->token = $token;
    }

    public function toArray() {
        return array_merge(parent::toArray(), [
            'token' => $this->token
        ]);
    }

}