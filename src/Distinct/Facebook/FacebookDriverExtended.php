<?php

namespace BotTemplateFramework\Distinct\Facebook;


use Illuminate\Support\Collection;
use BotMan\BotMan\Drivers\Events\GenericEvent;
use BotMan\BotMan\Interfaces\DriverEventInterface;
use BotMan\Drivers\Facebook\Events\MessagingCheckoutUpdates;
use BotMan\Drivers\Facebook\Events\MessagingDeliveries;
use BotMan\Drivers\Facebook\Events\MessagingOptins;
use BotMan\Drivers\Facebook\Events\MessagingReads;
use BotMan\Drivers\Facebook\Events\MessagingReferrals;
use BotMan\Drivers\Facebook\FacebookDriver;

class FacebookDriverExtended extends FacebookDriver {

    public function hasMatchingEvent()
    {
        $event = Collection::make($this->event->get('messaging'))->filter(function ($msg) {
            $fields = [
                'sender',
                'recipient',
                'timestamp',
                'message',
            ];
            foreach($fields as $field) {
                if (array_key_exists($field, $msg)) {
                    return false;
                }
            }
            if (array_key_exists('postback', $msg)) {
                return array_key_exists('referral', $msg['postback']);
            }
            return true;
        })->transform(function ($msg) {
            return Collection::make($msg)->toArray();
        })->first();

        if (! is_null($event)) {
            $this->driverEvent = $this->getEventFromEventData($event);

            return $this->driverEvent;
        }

        return false;
    }

    /**
     * @param array $eventData
     * @return DriverEventInterface
     */
    protected function getEventFromEventData(array $eventData)
    {
        $collection = Collection::make($eventData)->except([
            'sender',
            'recipient',
            'timestamp',
            'message'
        ]);

        if ($collection->has('referral') || ($collection->has('postback') && array_key_exists('referral', $collection->all()['postback']))) {
            return new MessagingReferrals($eventData);
        } elseif ($collection->has('optin')) {
            return new MessagingOptins($eventData);
        } elseif ($collection->has('delivery')) {
            return new MessagingDeliveries($eventData);
        } elseif ($collection->has('read')) {
            return new MessagingReads($eventData);
        } elseif ($collection->has('checkout_update')) {
            return new MessagingCheckoutUpdates($eventData);
        } else {
            $event = new GenericEvent($eventData);
            $event->setName($collection->keys()->first());
            return $event;
        }
    }

}