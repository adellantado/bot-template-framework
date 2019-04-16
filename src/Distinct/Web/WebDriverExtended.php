<?php

namespace BotTemplateFramework\Distinct\Web;

use BotMan\BotMan\Interfaces\DriverEventInterface;
use BotMan\Drivers\Web\WebDriver;
use BotTemplateFramework\Distinct\Web\Events\WidgetOpened;

class WebDriverExtended extends WebDriver {

    /** @var DriverEventInterface */
    protected $driverEvent;

    /**
     * @return bool|DriverEventInterface
     */
    public function hasMatchingEvent()
    {
        $event = $this->getEventFromEventData($this->payload);
        if ($event) {
            $this->driverEvent = $event;
            return $this->driverEvent;
        }
        return false;
    }

    /**
     * @param array $eventData
     *
     * @return bool|DriverEventInterface
     */
    public function getEventFromEventData(array $eventData)
    {
        switch ($this->event->get('eventName')) {
            case 'widgetOpened':
                return new WidgetOpened($eventData);
            default:
                return false;
        }
    }

}