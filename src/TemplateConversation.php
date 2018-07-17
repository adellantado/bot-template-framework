<?php

namespace BotTemplateFramework;


use BotMan\BotMan\BotMan;
use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\Question;

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
        $this->ask($question, function (Answer $answer) {
            $block = $this->engine->setBot($this->bot)
                ->getBlock($this->blockName);

            if (array_key_exists('result', $block) && array_key_exists('save', $block['result'])) {
                $this->engine->saveVariable($block['result']['save'], $answer->getText());
            }
            if (array_key_exists('next', $block)) {
                $this->engine->executeNextBlock($block, $answer->getText());
            }
        });
    }

}