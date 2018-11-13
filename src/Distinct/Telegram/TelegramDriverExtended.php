<?php

namespace BotTemplateFramework\Distinct\Telegram;


use Symfony\Component\HttpFoundation\Request;
use BotMan\Drivers\Telegram\TelegramDriver;

class TelegramDriverExtended extends TelegramDriver {

    public function buildPayload(Request $request)
    {
        $this->content = $request->getContent();
        parent::buildPayload($request);
    }

    public function messagesHandled() {
    }

}