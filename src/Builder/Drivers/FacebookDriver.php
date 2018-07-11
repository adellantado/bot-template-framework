<?php

namespace BotTemplateFramework\Builder\Drivers;


class FacebookDriver extends Driver {

    protected $token;
    protected $appSecret;
    protected $verification;

    public function __construct($token, $appSecret, $verification) {
        parent::__construct('Facebook');
        $this->token = $token;
        $this->appSecret = $appSecret;
        $this->verification = $verification;
    }

    public function toArray() {
        return array_merge(parent::toArray(), [
            'token' => $this->token,
            'appSecret' => $this->appSecret,
            'verification' => $this->verification
        ]);
    }

}