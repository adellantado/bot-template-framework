<?php

namespace BotTemplateFramework\Events;

class VariableRemovedEvent extends Event {

    public $variable;

    public function __construct($variable) {
        $this->variable = $variable;
        parent::__construct('variableRemoved');
    }

}