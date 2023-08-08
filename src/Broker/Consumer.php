<?php

namespace ADT\BackgroundQueueNette\Broker;

use ADT\BackgroundQueue\BackgroundQueue;
use Exception;
use PhpAmqpLib\Message\AMQPMessage;

class Consumer
{
	const NOOP = 'noop';

	private BackgroundQueue $backgroundQueue;

	public function __construct(BackgroundQueue $backgroundQueue)
	{
		$this->backgroundQueue = $backgroundQueue;
	}

	/**
	 * @throws Exception
	 */
	public function process(AMQPMessage $message): bool
	{
		$body = $message->getBody();

		if ($body === self::NOOP) {
			return true;
		}

		$this->backgroundQueue->process((int) $body);

		return true;
	}
}
