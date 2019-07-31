<?php


namespace BotTemplateFramework\Builder\Blocks;


class SaveBlock extends Block {

    protected $value;

    protected $variable;

    public function __construct($name = null) {
        parent::__construct('save', $name);
    }

    /**
     * @param $value
     * @return $this
     */
    public function value($value) {
        $this->value = $value;
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

    public function toArray() {
        return array_merge(parent::toArray(), [
            'variable' => $this->variable,
            'value' => $this->value
        ]);
    }

}