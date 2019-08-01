<?php


namespace BotTemplateFramework\Builder\Blocks;


use BotTemplateFramework\Builder\Prompt;

class ValidateBlock extends Block {

    protected $prompts = [];

    protected $validate;

    protected $variable;

    public function __construct($name = null) {
        parent::__construct('validate', $name);
    }

    /**
     * @param $validate
     * @return $this
     */
    public function validate($validate) {
        $this->validate = $validate;
        return $this;
    }

    /**
     * @param $variable
     * @return $this
     */
    public function variable($variable) {
        $this->variable = $variable;
        return $this;
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

        $array['variable'] = $this->variable;
        $array['validate'] = $this->validate;

        $array['next'] = [];
        foreach ($this->prompts as $prompt) {
            /** @var Prompt $prompt */
            $array['next'][$prompt->getText()] = $prompt->getNextBlock()->getName();
        }

        return $array;
    }

}