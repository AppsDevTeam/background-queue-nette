<?php

namespace ADT\BackgroundQueueNette\Broker;

use Kdyby\RabbitMq\Connection;

class Producer implements \ADT\BackgroundQueue\Broker\Producer
{
	const NOOP = 'noop';
	const PRODUCER_GENERAL = 'general';

	private Connection $connection;

	public function __construct(Connection $connection)
	{
		$this->connection = $connection;
	}

	public function publish(int $id, string $queue, ?int $expiration = null): void
	{
		$properties = [];
		if ($expiration) {
			$properties['expiration'] = (string)   $expiration;
		}
		$this->connection->getProducer($queue)->publish($id, '', $properties);
	}

	public function publishNoop(): void
	{
		$this->connection->getProducer(self::PRODUCER_GENERAL)->publish(self::NOOP);
	}
}