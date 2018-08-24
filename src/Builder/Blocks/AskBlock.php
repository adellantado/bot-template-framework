<?php

namespace BotTemplateFramework\Builder\Blocks;


use BotTemplateFramework\Builder\Prompt;
use BotTemplateFramework\Builder\Results\AskResult;

class AskBlock extends Block {

    /**
     * @var AskResult
     */
    protected $result;

    protected $prompts;

    protected $errorMsg;

    protected $validate;

    /**
     * @var string
     */
    protected $text;

    public function __construct($name = null) {
        parent::__construct('ask', $name);
    }

    public function result(AskResult $result) {
        $this->result = $result;
        return $this;
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
     * @param $validate
     * @return $this
     */
    public function validate($validate) {
        $this->validate = $validate;
        return $this;
    }

    /**
     * @param string $text
     * @return $this
     */
    public function errorMsg($text) {
        $this->errorMsg = $text;
        return $this;
    }

    /**
     * @param Block|array $next
     * @return Block
     */
    public function next($next) {
        if ($next instanceof Block) {
            parent::next($next);
        } else {
            $this->prompts = $next;
        }

        return $this;
    }

    public function toArray() {
        $array = array_merge(parent::toArray(), [
            'content' => $this->text
        ]);

        if ($this->result) {
            $array['result'] = $this->result->toArray();
        }

        if ($this->errorMsg) {
            $array['errorMsg'] = $this->errorMsg;
        }

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