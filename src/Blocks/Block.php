<?php

namespace BotTemplateFramework\Blocks;

use BotTemplateFramework\Drivers\Driver;

abstract class Block implements \JsonSerializable {

    protected $name;

    protected $type;

    /**
     * @var int
     */
    protected $typing;

    protected $locale = 'en';

    /**
     * @var Block
     */
    protected $next;

    protected $template;

    protected $drivers;



    public function __construct($type, $name = null) {
        if (is_null($name)) {
            $name = uniqid();
        }
        $this->type = $type;
        $this->name = $name;
    }

    /**
     * @param int $typing
     * @return Block
     */
    public function typing($typing)
    {
        $this->typing = $typing;
        return $this;
    }

    /**
     * @param mixed $locale
     * @return Block
     */
    public function locale($locale)
    {
        $this->locale = $locale;
        return $this;
    }

    /**
     * @param Block $next
     * @return Block
     */
    public function next($next)
    {
        $this->next = $next;
        return $this;
    }

    /**
     * @param string[]|Driver[] $drivers
     * @return $this
     */
    public function drivers($drivers) {
        $this->drivers = $drivers;
        return $this;
    }

    public function getName() {
        return $this->name;
    }

    /**
     * @param array $template
     * @return Block
     */
    public function template($template)
    {
        $this->template = $template;
        return $this;
    }


    public function jsonSerialize() {
        return $this->toArray();
    }

    public function toArray() {
        $array = [
            'name' => $this->name,
            'type' => $this->type,
            'locale' => $this->locale
        ];

        if ($this->typing) {
            $array['typing'] = $this->typing;
        }

        if ($this->template) {
            $array['template'] = implode(';', $this->template);
        }

        if ($this->next) {
            $array['next'] = $this->next->getName();
        }

        if ($this->drivers) {
            $array['drivers'] = '';
            foreach($this->drivers as $driver) {
                $array['drivers'] .= ($driver instanceof Driver ? strtolower($driver->getName()) : $driver).';';
            }
        }

        return $array;
    }

}