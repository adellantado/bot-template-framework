<?php

namespace BotTemplateFramework\Builder\Results;

use BotTemplateFramework\Builder\Prompt;

class AskResult extends Result {
    protected $prompts;

    public function prompts(array $prompts) {
        $this->prompts = $prompts;
        return $this;
    }

    public function toArray() {
        if ($this->prompts) {
            $array = [
                'prompt' => implode(';', array_map(function (Prompt $prompt) {
                    return $prompt->getText();
                }, $this->prompts)),
            ];
            return array_merge(parent::toArray(), $array);
        }

        return parent::toArray();
    }

}