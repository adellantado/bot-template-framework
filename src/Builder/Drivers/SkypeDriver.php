<?php

namespace BotTemplateFramework\Builder\Drivers;


class SkypeDriver extends Driver {
    protected $appId;
    protected $appKey;

    public function __construct($appId, $appKey) {
        parent::__construct('Skype');
        $this->appId = $appId;
        $this->appKey = $appKey;
    }

    public function toArray() {
        return array_merge(parent::toArray(), [
            'appId' => $this->appId,
            'appKey' => $this->appKey
        ]);
    }

}