<?php

namespace BotTemplateFramework\Distinct\Dialogflow;


use BotMan\BotMan\BotMan;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use BotMan\BotMan\Middleware\ApiAi;

class DialogflowExtended extends ApiAi {

    public function received(IncomingMessage $message, $next, BotMan $bot)
    {
        $response = $this->getResponse($message);

        $reply = $response->result->fulfillment->messages ?? [];
        $action = $response->result->action ?? '';
        $actionIncomplete = isset($response->result->actionIncomplete) ? (bool) $response->result->actionIncomplete : false;
        $intent = $response->result->metadata->intentName ?? '';
        $parameters = isset($response->result->parameters) ? (array) $response->result->parameters : [];

        $messages = [];
        $payloads = [];
        foreach ($reply as $msg) {
            if (property_exists($msg, 'speech')) {
                $messages[] = $msg->speech;
            }
            if (property_exists($msg, 'payload')) {
                if (property_exists($msg->payload, 'next')) {
                    $payloads['next'] = $msg->payload->next;
                }
            }
        }

        $message->addExtras('apiPayload', $payloads);
        $message->addExtras('apiReply', $messages);
        $message->addExtras('apiAction', $action);
        $message->addExtras('apiActionIncomplete', $actionIncomplete);
        $message->addExtras('apiIntent', $intent);
        $message->addExtras('apiParameters', $parameters);

        return $next($message);
    }

}