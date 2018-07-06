<?php

namespace BotTemplateFramework\Drivers;

abstract class Driver implements \JsonSerializable
{

    protected $name;

    protected $config;

    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * @param bool $bool
     * @return Driver
     */
    public function config($bool = false) {
        if ($bool) {
            $this->config = $bool;
        }
        return $this;
    }

    public function jsonSerialize() {
        return $this->toArray();
    }

    public function toArray() {
        $array = [
            'name' => $this->name
        ];

        if ($this->config) {
            $array['config'] = "true";
        }
        return $array;
    }
}