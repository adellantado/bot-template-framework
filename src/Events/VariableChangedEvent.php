<?php

namespace BotTemplateFramework\Events;

class VariableChangedEvent extends Event {

    public $variable;

    public $value;

    public function __construct($variable, $value) {
        $this->variable = $variable;
        $this->value = $value;
        parent::__construct('variableChanged');
    }

}