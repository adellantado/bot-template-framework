<?php

namespace BotTemplateFramework;


use BotMan\BotMan\BotMan;
use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotTemplateFramework\Helpers\Validator;

class TemplateConversation extends Conversation {

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

    public function run() {
        $engine = TemplateConversation::$engine;
        $block = $engine->getBlock($this->blockName);
        $question = null;
        if (array_key_exists('validate', $block) && $block['validate'] == 'email') {
            $question = $engine->strategy($this->bot)->requireEmailPayload($engine->parseText($block['content']));
        } elseif (array_key_exists('validate', $block) && $block['validate'] == 'phone') {
            $question = $engine->strategy($this->bot)->requirePhonePayload($engine->parseText($block['content']));
        }

        if ($question == null) {
            $question = new Question($engine->parseText($block['content']));
            if (array_key_exists('result', $block) && array_key_exists('prompt', $block['result'])) {
                $buttons = explode(';', $block['result']['prompt']);
                foreach ($buttons as $button) {
                    if ($button) {
                        $question->addButton(Button::create($button)->value($button));
                    }
                }
            }
        }


        $normalCallback = function(Answer $answer) {
            $engine = TemplateConversation::$engine;
            $block = $engine->setBot($this->bot)
                ->getBlock($this->blockName);

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
                $engine->saveVariable($block['result']['save'], $answer->getText());
            }

            if ($engine->callListener($block) === false) {
                return;
            }

            if (array_key_exists('next', $block)) {
                $engine->executeNextBlock($block, $answer->getText());
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
            $this->ask($question, $normalCallback);
        }
    }

    public function askAgain($block) {
        $engine = TemplateConversation::$engine;
        $conversation = new TemplateConversation($this);
        $conversation->blockName = $block['name'];
        $this->bot->startConversation($conversation);
    }

}