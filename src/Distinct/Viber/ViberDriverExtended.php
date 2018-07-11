<?php

namespace BotTemplateFramework\Distinct\Viber;


use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use TheArdent\Drivers\Viber\ViberDriver;

class ViberDriverExtended extends ViberDriver {

    public function buildPayload(Request $request) {
        $this->payload = new ParameterBag((array)json_decode($request->getContent(), true));
        $this->event = Collection::make($this->payload->get('event'));
        $this->config = Collection::make($this->config->get('viber'));
    }

    public function getEventFromEventData(array $eventData) {
        if ($this->event->first() == 'webhook') {
            return new WebhookEvent($eventData);
        }

        return parent::getEventFromEventData($eventData);
    }

    /**
     * @param Request $request
     *
     * @return bool
     */
    public function verifyRequest(Request $request) {
        if ($request->get('sig')) {
            return parent::verifyRequest($request);
        }
        return false;
    }

}