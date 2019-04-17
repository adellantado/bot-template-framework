<?php

namespace BotTemplateFramework\Distinct\Chatbase;

use BotMan\BotMan\BotMan;
use BotMan\BotMan\Interfaces\MiddlewareInterface;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use BotTemplateFramework\TemplateEngine;
use Illuminate\Support\Facades\Log;

class ChatbaseExtended implements MiddlewareInterface {

    protected $cb;

    protected $engine;

    protected $optimized;

    public static function create($engine, $optimized = false) {
        return new static($engine, true, $optimized);
    }

    public function __construct(TemplateEngine $engine, $applyMiddleware = true, $optimized = false) {
        $this->engine = $engine;
        $this->optimized = $optimized;
        $this->cb = new \ChatbaseAPI\Chatbase($engine->getDriver('chatbase')['token']);
        if ($applyMiddleware) {
            $this->engine->getBot()->middleware->received($this);
            $this->engine->getBot()->middleware->sending($this);
        }
    }

    public function sendAgentMessage($text, $session_id = "") {
        $cb_data = $this->cb->agentMessage(
            $this->engine->getVariable('user.id'),
            $this->engine->getVariable('bot.driver'),
            $text,
            $session_id, '1.x'
        );
        if ($this->cb->send($cb_data)->status != 200) {
            Log::error('Chatbase error sending agent message');
        }
    }

    public function sendUserMessage($text, $intent = "", $session_id = "", $not_handled = false, $feedback = false) {
        $cb_data = $this->cb->userMessage(
            $this->engine->getVariable('user.id'),
            $this->engine->getVariable('bot.driver'),
            $text,
            $intent, $session_id, '1.x',
            $not_handled, $feedback
        );
        if ($this->cb->send($cb_data)->status != 200) {
            Log::error('Chatbase error sending user message');
        }
    }

    public function sendTwoWayMessages($userText, $agentText, $intent = "", $session_id = "", $not_handled = false) {
        $cb_data = $this->cb->twoWayMessages(
            $this->engine->getVariable('user.id'),
            $this->engine->getVariable('bot.driver'),
            $userText, $agentText,
            $intent, $session_id, '1.x', '1.x',
            $not_handled
        );
        if ($this->cb->sendAll($cb_data)->all_succeeded != true) {
            Log::error('Chatbase error sending two way messages');
        }
    }

    public function sendRawMessages($messages) {
        foreach ($messages as &$message) {
            $message['user_id'] = $this->engine->getVariable('user.id');
            $message['platform'] = $this->engine->getVariable('bot.driver');
            $message['version'] = '1.x';
        }
        $cb_data = $this->cb->rawMultipleMessages($messages);
        if ($this->cb->sendAll($cb_data)->all_succeeded != true) {
            Log::error('Chatbase error sending raw messages');
        }
    }

    public function sending($payload, $next, BotMan $bot) {
        $text = is_array($payload) ? json_encode($payload, JSON_UNESCAPED_UNICODE) : (string)$payload;
        if (!$this->optimized) {
            $this->sendAgentMessage($text);
        } else {
            $this->sendTwoWayMessages($this->engine->getVariable('message'), $text);
        }
        return $next($payload);
    }

    public function received(IncomingMessage $message, $next, BotMan $bot) {
        if (!$this->optimized) {
            $this->sendUserMessage($message->getText());
        }
        return $next($message);
    }

    public function captured(IncomingMessage $message, $next, BotMan $bot) {
        return $next($message);
    }

    public function heard(IncomingMessage $message, $next, BotMan $bot) {
        return $next($message);
    }

    public function matching(IncomingMessage $message, $pattern, $regexMatched) {
        return true;
    }

}