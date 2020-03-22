<?php

namespace BotTemplateFramework;


use BotMan\BotMan\BotMan;
use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotTemplateFramework\Helpers\Validator;
use BotTemplateFramework\Strategies\StrategyTrait;

class TemplateConversation extends Conversation {

    /**
     * Alternative to blockName.
     * Stores whole block in a cache instead of blok's name.
     * Useful for dynamic blocks
     *
     * @var array
     */
    public $block;

    public $blockName;

    /**
     * @var TemplateEngine
     */
    static $engine;

    /**
     * @return BotMan
     */
    public function getBot() {
        return $this->bot;
    }
    
    public function getBlock() {
        if ($this->block) {
            return $this->block;
        }
        return TemplateConversation::$engine->getBlock($this->blockName);
    }

    public function run() {
        $engine = TemplateConversation::$engine;
        $block = $this->getBlock();
        $question = null;
        $isValidation = array_key_exists('validate', $block);
        $rules = explode('|', $block['validate'] ?? '');
        if ($isValidation) {
            if (in_array('email', $rules)) {
                $question = $engine->strategy($this->bot)->requireEmailPayload($engine->getText($block['content']), $block['options'] ?? null);
            } elseif (in_array('phone', $rules)) {
                $question = $engine->strategy($this->bot)->requirePhonePayload($engine->getText($block['content']), $block['options'] ?? null);
            } elseif (in_array('location', $rules)) {
                $question = $engine->strategy($this->bot)->requireLocationPayload($engine->getText($block['content']), $block['options'] ?? null);
            }
        }


        if ($question == null) {
            $question = new Question($engine->getText($block['content']));
            if (array_key_exists('result', $block) && array_key_exists('prompt', $block['result'])) {
                $buttons = explode(';', $block['result']['prompt']);
                foreach ($buttons as $button) {
                    $text = $engine->parseText($button);
                    if ($text) {
                        $question->addButton(Button::create($text)->value($text));
                    }
                }
            }
        }


        $normalCallback = function(Answer $answer) {
            $engine = TemplateConversation::$engine->setBot($this->bot);
            $block= $this->getBlock();

            $isValidation = array_key_exists('validate', $block);
            $rules = explode('|', $block['validate'] ?? '');

            if ($isValidation) {
                $validator = new Validator();

                foreach ($rules as $rule) {
                    $valid = $validator->validate($rule,$answer->getText(),function($msg) use ($block){
                        $this->say($block['errorMsg'] ?? $msg);
                        $this->askAgain($block);
                    });

                    if (!$valid) {
                        return;
                    }
                }
            }

            if (array_key_exists('result', $block) && array_key_exists('save', $block['result'])) {
                $driver = StrategyTrait::driverName($this->bot);
                if ($isValidation && in_array('location', $rules)) {
                    $location = $answer->getMessage()->getLocation() ?? null;
                    $text = '';
                    if ($location) {
                        $text = $location->getLatitude() . ',' . $location->getLongitude();
                    }
                    $engine->saveVariable($block['result']['save'], $text);
                } elseif ($isValidation && in_array('audio', $rules)) {
                    $engine->saveVariable($block['result']['save'], $answer->getMessage()->getAudio()[0]->getUrl());
                } elseif ($isValidation && in_array('video', $rules)) {
                    $engine->saveVariable($block['result']['save'], $answer->getMessage()->getVideos()[0]->getUrl());
                } elseif ($isValidation && in_array('file', $rules)) {
                    $engine->saveVariable($block['result']['save'], $answer->getMessage()->getFiles()[0]->getUrl());
                } elseif ($isValidation && in_array('image', $rules)) {
                    $engine->saveVariable($block['result']['save'], $answer->getMessage()->getImages()[0]->getUrl());
                } elseif ($isValidation && in_array('phone', $rules) && in_array($driver, ['Telegram', 'Viber'])) {
                    $payload = $answer->getMessage()->getPayload();
                    if ($driver == 'Telegram') {
                        $phone = $payload->get('contact')['phone_number'];
                    } else {
                        $phone = $payload->get('message')['contact']['phone_number'];
                    }
                    $engine->saveVariable($block['result']['save'], ($phone ? $phone : $answer->getText()));
                } else {
                    $engine->saveVariable($block['result']['save'], $answer->getText());
                }
            }

            if ($engine->callListener($block) === false) {
                return;
            }

            if (array_key_exists('next', $block)) {
                if ($this->block) {
                    $nextBlockName = null;
                    if (is_array($block['next'])) {
                        $key = $answer->getText();
                        if ($key && array_key_exists($key, $block['next'])) {
                            $nextBlockName = $block['next'][$key];
                        } elseif (array_key_exists('fallback', $block['next'])) {
                            $nextBlockName = $block['next']['fallback'];
                        }
                    } else {
                        $nextBlockName = $block['next'];
                    }
                    if ($nextBlockName && $nextBlockName == $block['name']) {
                        $engine->executeBlock($block);
                    } else {
                        $engine->executeNextBlock($block, $answer->getText());
                    }
                } else {
                    $engine->executeNextBlock($block, $answer->getText());
                }
            }
        };

        $confirmationCallback = function(Answer $answer) use ($normalCallback) {
            $engine = TemplateConversation::$engine;
            $engine->setBot($this->bot);
            $engine->saveVariable('{{temp.confirmation}}', $answer->getText());
            $question = new Question('Confirm, please, by typing one more time');
            $this->ask($question, $normalCallback);
        };

        if ($isValidation && in_array('confirm', $rules)) {
            $this->ask($question, $confirmationCallback);
        } else {

            if ($isValidation && (in_array('location', $rules) || in_array('phone', $rules)) && StrategyTrait::driverName($this->bot) == 'Telegram') {
                $this->ask($question['text'], $normalCallback, $question['additionalParameters']);
            } else {
                $this->ask($question, $normalCallback);
            }
        }
    }

    public function askAgain($block) {
        $conversation = new TemplateConversation($this);
        if ($this->block) {
            $conversation->block = $block;
        } else {
            $conversation->blockName = $block['name'];
        }
        $this->bot->startConversation($conversation);
    }

    public function skipsConversation(IncomingMessage $message) {
        $block = $this->getBlock();
        if ($block['skip'] ?? '') {
            $skip = explode(';', $block['skip']) ?? [];
            foreach ($skip as $item) {
                if ($message->getText() == $item) {
                    return true;
                }
            }
        }
        return false;
    }

    public function stopsConversation(IncomingMessage $message) {
        $block = $this->getBlock();
        if ($block['stop'] ?? '') {
            $stop = explode(';', $block['stop']) ?? [];
            foreach ($stop as $item) {
                if ($message->getText() == $item) {
                    return true;
                }
            }
        }
        return false;
    }

}