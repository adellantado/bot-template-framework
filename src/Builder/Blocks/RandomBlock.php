<?php

namespace BotTemplateFramework\Builder\Blocks;


use BotTemplateFramework\Builder\Prompt;

class RandomBlock extends Block {

    protected $prompts = [];

    public function __construct($name = null) {
        parent::__construct('random', $name);
    }


    /**
     * @param array $next
     * @return Block
     */
    public function next($next) {
        $this->prompts = $next;
        return $this;
    }

    public function toArray() {
        $array = parent::toArray();

        if ($this->prompts) {
            $array['next'] = [];
            foreach ($this->prompts as $prompt) {
                /** @var Prompt $prompt */
                $array['next'][$prompt->getText()] = $prompt->getNextBlock()->getName();
            }
        }

        return $array;
    }

}