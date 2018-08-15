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
    public $engine;

    /**
     * @return BotMan
     */
    public function getBot() {
        return $this->bot;
    }

    public function run() {
        $block = $this->engine->getBlock($this->blockName);
        $question = new Question($this->engine->parseText($block['content']));
        if (array_key_exists('result', $block) && array_key_exists('prompt', $block['result'])) {
            $buttons = explode(';', $block['result']['prompt']);
            foreach ($buttons as $button) {
                if ($button) {
                    $question->addButton(Button::create($button)->value($button));
                }
            }
        }

        $normalCallback = function(Answer $answer) {
            $block = $this->engine->setBot($this->bot)
                ->getBlock($this->blockName);

            if (array_key_exists('validate', $block)) {
                $validator = new Validator();
                if ($block['validate'] == 'number') {
                    if (!$validator->number($answer->getText())) {
                        $this->say($validator->errorNumberMsg());
                        $this->askAgain($block);
                        return;
                    }
                } elseif ($block['validate'] == 'url') {
                    if (!$validator->url($answer->getText())) {
                        $this->say($validator->errorUrlMsg());
                        $this->askAgain($block);
                        return;
                    }
                } elseif ($block['validate'] == 'email') {
                    if (!$validator->email($answer->getText())) {
                        $this->say($validator->errorEmailMsg());
                        $this->askAgain($block);
                        return;
                    }
                } elseif ($block['validate'] == 'confirm') {
                    $oldValue = $this->engine->removeVariable('temp.confirmation');
                    if (!$validator->confirm($oldValue, $answer->getText())) {
                        $this->say($validator->errorConfirmMsg());
                        $this->askAgain($block);
                        return;
                    }
                } else {
                    if (!$validator->regexp($block['validate'], $answer->getText())) {
                        $this->say('Can\'t validate input');
                        $this->askAgain($block);
                        return;
                    }
                }
            }

            if (array_key_exists('result', $block) && array_key_exists('save', $block['result'])) {
                $this->engine->saveVariable($block['result']['save'], $answer->getText());
            }

            $this->engine->callListener($block);

            if (array_key_exists('next', $block)) {
                $this->engine->executeNextBlock($block, $answer->getText());
            }
        };

        $confirmationCallback = function(Answer $answer) use ($normalCallback) {
            $this->engine->saveVariable('{{temp.confirmation}}', $answer->getText());
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
        $conversation = new TemplateConversation($this);
        $conversation->blockName = $block['name'];
        $conversation->engine = $this->engine;
        $this->bot->startConversation($conversation);
    }

}