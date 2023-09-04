<?php

namespace ADT\BackgroundQueueNette\Broker;

use Exception;
use Kdyby\RabbitMq\Connection;
use PhpAmqpLib\Message\AMQPMessage;

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
		$properties = [
			'content_type' => 'text/plain',
			'delivery_mode' => 2,
		];
		if ($expiration) {
			$properties['expiration'] = (string)   $expiration;
		}
		$this->connection->getProducer($queue)->getChannel()->set_return_listener(
			function ($replyCode, $replyText, $exchange, $routingKey, AMQPMessage $message) {
				throw new Exception("Code: $replyCode, Text: $replyText, Exchange: $exchange, Routing Key: $routingKey");
			}
		);
		$this->connection->getProducer($queue)->getChannel()->basic_publish(new AMQPMessage($id, $properties), '', '', true);
		$this->connection->getProducer($queue)->getChannel()->wait();
	}

	public function publishNoop(): void
	{
		$this->connection->getProducer(self::PRODUCER_GENERAL)->publish(self::NOOP);
	}
}