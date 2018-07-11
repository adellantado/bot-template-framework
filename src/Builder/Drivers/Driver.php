<?php

namespace BotTemplateFramework\Builder\Drivers;

abstract class Driver implements \JsonSerializable {

    protected $name;

    protected $config;

    protected $events;

    public function __construct($name) {
        $this->name = $name;
    }

    public function getName() {
        return $this->name;
    }

    public function events($array) {
        $this->events = $array;
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

        if ($this->events) {
            $array['events'] = $this->events;
        }

        if ($this->config) {
            $array['config'] = "true";
        }
        return $array;
    }
}