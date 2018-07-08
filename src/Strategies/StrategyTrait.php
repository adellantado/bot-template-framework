<?php

namespace BotTemplateFramework\Strategies;

use BotMan\BotMan\BotMan;

trait StrategyTrait {
    /**
     * @var IComponentsStrategy
     */
    protected $strategy;

    public function strategy(BotMan $bot) {
        if ($this->strategy) {
            return $this->strategy;
        }

        return $this->strategy = StrategyTrait::initStrategy($bot);
    }

    public static function initStrategy(BotMan $bot) {
        $driver = $bot->getDriver();
        $driveName = null;

        if ($driver instanceof \BotMan\Drivers\BotFramework\BotFrameworkDriver) {
            $driveName = 'BotFramework';
        } elseif ($driver instanceof \BotMan\Drivers\Facebook\FacebookDriver) {
            $driveName = 'Facebook';
        } elseif ($driver instanceof \BotMan\Drivers\Telegram\TelegramDriver) {
            $driveName = 'Telegram';
        } elseif ($driver instanceof \TheArdent\Drivers\Viber\ViberDriver) {
            $driveName = 'Viber';
        } elseif ($driver instanceof \BotMan\Drivers\Web\WebDriver) {
            $driveName = 'Web';
        }

        if ($driveName) {
            $clazz = "App\\Strategies\\" . $driveName;
            $componentsStrategy = "BotTemplateFramework\\Strategies\\" . $driveName . "ComponentsStrategy";

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