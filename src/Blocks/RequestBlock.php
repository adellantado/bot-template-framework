<?php

namespace BotTemplateFramework\Blocks;


use BotTemplateFramework\Prompt;
use BotTemplateFramework\Results\RequestResult;

class RequestBlock extends Block {
    /**
     * @var string
     */
    protected $method = 'GET';

    protected $url;

    protected $body;

    /**
     * @var RequestResult
     */
    protected $result;

    protected $prompts;

    public function __construct($name = null) {
        parent::__construct('request', $name);
    }

    /**
     * @param string $method
     * @return $this
     */
    public function method($method) {
        $this->method = $method;
        return $this;
    }

    /**
     * @param string $url
     * @return $this
     */
    public function url($url) {
        $this->url = $url;
        return $this;
    }

    public function result(RequestResult $result) {
        $this->result = $result;
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
            'url' => $this->url,
            'method' => $this->method,
        ]);

        if ($this->body) {
            $array['body'] = $this->body;
        }

        if ($this->result) {
            $array['result'] = $this->result->toArray();
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