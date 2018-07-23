<?php

namespace BotTemplateFramework\Builder\Results;

use BotTemplateFramework\Builder\Prompt;

class AskResult extends Result {
    protected $prompts;

    protected $validate;

    public function prompts(array $prompts) {
        $this->prompts = $prompts;
        return $this;
    }

    public function validate($pattern) {
        $this->variable = $pattern;
        return $this;
    }

    public function toArray() {
        $array = [];

        if ($this->prompts) {
            $array['prompt'] = implode(';', array_map(function (Prompt $prompt) {
                return $prompt->getText();
            }, $this->prompts));
        }

        if ($this->variable) {
            $array['validate'] = $this->validate;
        }

        return array_merge(parent::toArray(), $array);
    }

}