<?php

namespace ADT\BackgroundQueue\Command;

use ADT\BackgroundQueue\BackgroundQueueRabbitMQ;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ReloadConsumersCommand extends Command
{
	protected static $defaultName = 'background-queue:reload-consumers';

	protected BackgroundQueueRabbitMQ $backgroundQueueRabbitMQ;
	
	public function __construct(BackgroundQueueRabbitMQ $backgroundQueueRabbitMQ) 
	{
		parent::__construct();
		$this->backgroundQueueRabbitMQ = $backgroundQueueRabbitMQ;
	}

	protected function configure()
	{
		$this->addArgument(
			"number",
			InputArgument::REQUIRED,
			'Number of consumers to reload.'
		);
		$this->setDescription('Creates the specified number of noop messages to reload consumers.');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		for ($i = 0; $i < $input->getArgument("number"); $i++) {
			$this->backgroundQueueRabbitMQ->publishNoop();
		}

		return 0;
	}
}
