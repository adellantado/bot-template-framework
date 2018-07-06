<?php

namespace BotTemplateFramework\Results;

abstract class Result implements \JsonSerializable
{

    protected $variable;

    public function save($variable) {
        $this->variable = $variable;
        return $this;
    }

    public function jsonSerialize() {
        return $this->toArray();
    }

    public function toArray() {
        if ($this->variable) {
            return [
                'save' => $this->variable,
            ];
        }

        return [];

    }

}