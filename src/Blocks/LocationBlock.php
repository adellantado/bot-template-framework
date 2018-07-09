<?php

namespace BotTemplateFramework\Blocks;


use BotTemplateFramework\Results\Result;

class LocationBlock extends Block {

    /**
     * @var string
     */
    protected $text;

    /**
     * @var Result
     */
    protected $result;

    public function __construct($name = null) {
        parent::__construct('location', $name);
    }

    /**
     * @param string $text
     * @return $this
     */
    public function text($text) {
        $this->text = $text;
        return $this;
    }

    public function result(Result $result) {
        $this->result = $result;
        return $this;
    }

    public function toArray() {
        $array =  array_merge(parent::toArray(), [
            'content' => $this->text
        ]);

        if ($this->result) {
            $array['result'] = $this->result->toArray();
        }

        return $array;
    }

}