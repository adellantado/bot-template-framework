<?php


namespace BotTemplateFramework\Builder\Drivers;


class ChatbaseDriver extends Driver {

    protected $token;

    public function __construct($token) {
        parent::__construct('Chatbase');
        $this->token = $token;
    }

    public function toArray() {
        return array_merge(parent::toArray(), [
            'token' => $this->token
        ]);
    }

}