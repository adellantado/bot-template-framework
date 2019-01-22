<?php


namespace BotTemplateFramework\Events;


class ListenStartedEvent extends Event {

    public function __construct() {
        parent::__construct('listenStarted');
    }

}