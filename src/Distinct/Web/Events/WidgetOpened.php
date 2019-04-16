<?php

namespace BotTemplateFramework\Distinct\Web\Events;


use BotMan\BotMan\Interfaces\DriverEventInterface;

class WidgetOpened implements DriverEventInterface
{

	protected $payload;
	/**
	 * @param $payload
	 */
	public function __construct($payload)
	{
		$this->payload = $payload;
	}

	/**
	 * Return the event name to match.
	 *
	 * @return string
	 */
	public function getName()
	{
		return 'widgetOpened';
	}

	/**
	 * Return the event payload.
	 *
	 * @return mixed
	 */
	public function getPayload()
	{
		return $this->payload;
	}

}