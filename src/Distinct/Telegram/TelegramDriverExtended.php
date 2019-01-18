<?php

namespace BotTemplateFramework\Distinct\Telegram;

use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use Illuminate\Support\Collection;
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

    public function loadMessages()
    {
        if ($this->payload->get('callback_query') !== null) {
            $callback = Collection::make($this->payload->get('callback_query'));

            $messages = [
                new IncomingMessage($callback->get('data'), $callback->get('from')['id'],
                    $callback->get('message')['chat']['id'], $callback->get('message')),
            ];
        } elseif ($this->payload->get('contact') !== null) {
            $messages = [
                new IncomingMessage($this->event->get('contact')['phone_number'], $this->event->get('from')['id'], $this->event->get('chat')['id'],
                    $this->event),
            ];
        } elseif ($this->isValidLoginRequest()) {
            $messages = [
                new IncomingMessage('', $this->queryParameters->get('id'), $this->queryParameters->get('id'), $this->queryParameters),
            ];
        } else {
            $messages = [
                new IncomingMessage($this->event->get('text'), $this->event->get('from')['id'], $this->event->get('chat')['id'],
                    $this->event),
            ];
        }

        $this->messages = $messages;
    }

}