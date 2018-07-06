<?php

namespace BotTemplateFramework;

use BotTemplateFramework\Blocks\Block;
use BotTemplateFramework\Drivers\Driver;

class Template implements \JsonSerializable {

    protected $name;

    protected $blocks = [];

    protected $drivers = [];

    protected $fallback;

    public function __construct($name) {
        $this->name = $name;
    }

    public function getName() {
        return $this->name;
    }

    public function addBlocks(array $blocks) {
        $this->blocks = array_merge($this->blocks, $blocks);
        return $this;
    }

    public function getBlocks() {
        return $this->blocks;
    }

    public function addDrivers(array $drivers) {
        $this->drivers = array_merge($this->drivers, $drivers);
        return $this;
    }

    public function getDrivers() {
        return $this->drivers;
    }

    public function addFallbackMessage($message) {
        $this->fallback = $message;
        return $this;
    }

    public function jsonSerialize() {
        return $this->toArray();
    }

    public function toArray() {
        return [
            'name' => $this->name,
            'fallback' => $this->fallback,
            'blocks' => array_map(function(Block $block){return $block->jsonSerialize();}, $this->blocks),
            'drivers' => array_map(function(Driver $driver){return $driver->jsonSerialize();}, $this->drivers)
        ];
    }

}