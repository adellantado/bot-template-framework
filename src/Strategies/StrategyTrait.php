<?php

namespace BotTemplateFramework\Strategies;

use BotMan\BotMan\BotMan;

trait StrategyTrait
{
    /**
     * @var IComponentsStrategy
     */
    protected $strategy;

    public function strategy(BotMan $bot)
    {
        if ($this->strategy) {
            return $this->strategy;
        }

        return $this->strategy = StrategyTrait::initStrategy($bot);
    }

    public static function initStrategy(BotMan $bot) {
        $driveName = $bot->getDriver()->getName();

        if (in_array($driveName, [
            'BotFramework',
            'Facebook',
            'Telegram',
            'Slack',
            'Viber',
            'Web'
        ])) {
            $clazz = "App\\Strategies\\".$driveName;
            $componentsStrategy = "BotTemplateFramework\\Strategies\\".$driveName."ComponentsStrategy";

            $is_class_exists = class_exists($clazz);
            if ($is_class_exists) {
                $instance = new $clazz($bot);
                if ($instance instanceof Strategy) {
                    $instance->setComponentsStrategy(new $componentsStrategy($bot));
                }
            } else {
                $instance = new $componentsStrategy($bot);
            }
            return $instance;
        }
        return null;
    }
}