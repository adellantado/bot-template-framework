<?php

namespace BotTemplateFramework\Distinct\Viber;


use TheArdent\Drivers\Viber\Events\ViberEvent;

class WebhookEvent extends ViberEvent
{

    /**
     * Return the event name to match.
     *
     * @return string
     */
    public function getName()
    {
        return 'webhook';
    }
}