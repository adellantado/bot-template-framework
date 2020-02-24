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
        if (array_key_exists('validate', $block) && $block['validate'] == 'email') {
            $question = $engine->strategy($this->bot)->requireEmailPayload($engine->getText($block['content']), $block['options'] ?? null);
        } elseif (array_key_exists('validate', $block) && $block['validate'] == 'phone') {
            $question = $engine->strategy($this->bot)->requirePhonePayload($engine->getText($block['content']), $block['options'] ?? null);
        } elseif (array_key_exists('validate', $block) && $block['validate'] == 'location') {
            $question = $engine->strategy($this->bot)->requireLocationPayload($engine->getText($block['content']), $block['options'] ?? null);
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

            if (array_key_exists('validate', $block)) {
                $validator = new Validator();
                if ($block['validate'] == 'number') {
                    if (!$validator->number($answer->getText())) {
                        $this->say($block['errorMsg'] ?? $validator->errorNumberMsg());
                        $this->askAgain($block);
                        return;
                    }
                } elseif ($block['validate'] == 'url') {
                    if (!$validator->url($answer->getText())) {
                        $this->say($block['errorMsg'] ?? $validator->errorUrlMsg());
                        $this->askAgain($block);
                        return;
                    }
                } elseif ($block['validate'] == 'email') {
                    if (!$validator->email($answer->getText())) {
                        $this->say($block['errorMsg'] ?? $validator->errorEmailMsg());
                        $this->askAgain($block);
                        return;
                    }
                } elseif ($block['validate'] == 'phone') {
                    if (!$validator->phone($answer->getText())) {
                        $this->say($block['errorMsg'] ?? $validator->errorPhoneMsg());
                        $this->askAgain($block);
                        return;
                    }
                } elseif ($block['validate'] == 'image') {
                    if (!$validator->image($answer->getText())) {
                        $this->say($block['errorMsg'] ?? $validator->errorImageMsg());
                        $this->askAgain($block);
                        return;
                    }
                } elseif ($block['validate'] == 'file') {
                    if (!$validator->file($answer->getText())) {
                        $this->say($block['errorMsg'] ?? $validator->errorFileMsg());
                        $this->askAgain($block);
                        return;
                    }
                } elseif ($block['validate'] == 'video') {
                    if (!$validator->video($answer->getText())) {
                        $this->say($block['errorMsg'] ?? $validator->errorVideoMsg());
                        $this->askAgain($block);
                        return;
                    }
                } elseif ($block['validate'] == 'audio') {
                    if (!$validator->audio($answer->getText())) {
                        $this->say($block['errorMsg'] ?? $validator->errorAudioMsg());
                        $this->askAgain($block);
                        return;
                    }
                } elseif ($block['validate'] == 'location') {
                    if (!$validator->location($answer->getText())) {
                        $this->say($block['errorMsg'] ?? $validator->errorLocationMsg());
                        $this->askAgain($block);
                        return;
                    }
                } elseif ($block['validate'] == 'confirm') {
                    $oldValue = $engine->removeVariable('temp.confirmation');
                    if (!$validator->confirm($oldValue, $answer->getText())) {
                        $this->say($block['errorMsg'] ?? $validator->errorConfirmMsg());
                        $this->askAgain($block);
                        return;
                    }
                } else {
                    if (!$validator->regexp($block['validate'], $answer->getText())) {
                        $this->say($block['errorMsg'] ?? $validator->errorRegexpMsg());
                        $this->askAgain($block);
                        return;
                    }
                }
            }

            if (array_key_exists('result', $block) && array_key_exists('save', $block['result'])) {
                if (array_key_exists('validate', $block) && $block['validate'] == 'location') {
                    $location = $answer->getMessage()->getLocation() ?? null;
                    $text = '';
                    if ($location) {
                        $text = $location->getLatitude() . ',' . $location->getLongitude();
                    }
                    $engine->saveVariable($block['result']['save'], $text);
                } elseif (array_key_exists('validate', $block) && $block['validate'] == 'audio') {
                    $engine->saveVariable($block['result']['save'], $answer->getMessage()->getAudio()[0]->getUrl());
                } elseif (array_key_exists('validate', $block) && $block['validate'] == 'video') {
                    $engine->saveVariable($block['result']['save'], $answer->getMessage()->getVideos()[0]->getUrl());
                } elseif (array_key_exists('validate', $block) && $block['validate'] == 'file') {
                    $engine->saveVariable($block['result']['save'], $answer->getMessage()->getFiles()[0]->getUrl());
                } elseif (array_key_exists('validate', $block) && $block['validate'] == 'image') {
                    $engine->saveVariable($block['result']['save'], $answer->getMessage()->getImages()[0]->getUrl());
                } elseif (array_key_exists('validate', $block) && $block['validate'] == 'phone' && StrategyTrait::driverName($this->bot) == 'Telegram') {
                    $payload = $answer->getMessage()->getPayload();
                    $engine->saveVariable($block['result']['save'], $payload->get('contact')['phone_number']);
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

        if (array_key_exists('validate', $block) && $block['validate'] == 'confirm') {
            $this->ask($question, $confirmationCallback);
        } else {

            if (array_key_exists('validate', $block) && in_array($block['validate'], ['phone', 'location']) && StrategyTrait::driverName($this->bot) == 'Telegram') {
                $this->ask($question['text'], $normalCallback, $question['additionalParameters']);
            } else {
                $this->ask($question, $normalCallback);
            }
        }
    }

    public function askAgain($block) {
        $engine = TemplateConversation::$engine;
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