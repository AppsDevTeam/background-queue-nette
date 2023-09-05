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

		$producer =  $this->connection->getProducer($queue);
		$channel = $producer->getChannel();

		if ($expiration) {
			$options = $producer->getQueueOptions();
			$producer->getChannel()->queue_declare(
				$queue . '_' . $expiration,
				$options['passive'],
				$options['durable'],
				$options['exclusive'],
				$options['autoDelete'],
				$options['nowait'],
				$options['arguments'],
				$options['ticket']
			);
		}

		$channel->confirm_select();
		$channel->set_nack_handler(function (AMQPMessage $message) {
			throw new Exception('Internal error (basic.nack)');
		});
		$channel->set_return_listener(
			function ($replyCode, $replyText, $exchange, $routingKey, AMQPMessage $message) {
				throw new Exception("Code: $replyCode, Text: $replyText, Exchange: $exchange, Routing Key: $routingKey");
			}
		);
		$channel->basic_publish(new AMQPMessage($id, $properties), $producer->getExchangeOptions()['name'], $expiration ? $queue . '_' . $expiration : '', true);
		$channel->wait_for_pending_acks_returns();
	}

	public function publishNoop(): void
	{
		$this->connection->getProducer(self::PRODUCER_GENERAL)->publish(self::NOOP);
	}
}