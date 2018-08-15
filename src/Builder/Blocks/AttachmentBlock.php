<?php

namespace BotTemplateFramework\Builder\Blocks;


use BotTemplateFramework\Builder\Results\Result;

class AttachmentBlock extends Block {

    /**
     * @var string
     */
    protected $text;

    /**
     * @var string
     */
    protected $mode;

    /**
     * @var Result
     */
    protected $result;

    public function __construct($name = null) {
        parent::__construct('attachment', $name);
    }

    /**
     * @param string $text
     * @return $this
     */
    public function text($text) {
        $this->text = $text;
        return $this;
    }

    /**
     * @param string $mode
     * @return $this
     */
    public function mode($mode) {
        $this->mode = $mode;
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

        if ($this->mode) {
            $array['mode'] = $this->mode;
        }

        if ($this->result) {
            $array['result'] = $this->result->toArray();
        }

        return $array;
    }

}