<?php

namespace ADT\BackgroundQueueNette\DI;

use ADT\BackgroundQueue\BackgroundQueue;
use ADT\BackgroundQueue\Console\ClearFinishedCommand;
use ADT\BackgroundQueue\Console\ConsumeCommand;
use ADT\BackgroundQueue\Console\ProcessCommand;
use ADT\BackgroundQueue\Console\ReloadConsumersCommand;
use ADT\BackgroundQueue\Console\UpdateSchemaCommand;
use Nette\DI\CompilerExtension;
use Nette\Schema\Expect;
use Nette\Schema\Processor;
use Nette\Schema\Schema;
use stdClass;

/** @noinspection PhpUnused */
class BackgroundQueueExtension extends CompilerExtension
{
	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'callbacks' => Expect::arrayOf(
				Expect::anyOf(
					Expect::type('callable'),  // callbackName => callback
					Expect::structure([              // callbackName => callback + queue
						'callback' => Expect::type('callable')->required(),
						'queue' => Expect::string()->nullable(),
						'priority' => Expect::int()->min(1)->nullable(),
					])
				),
				'string'
			)->required(),
			'notifyOnNumberOfAttempts' => Expect::int()->min(1)->required(),
			'tempDir' => Expect::string()->required(),
			'locksDir' => Expect::string()->required(),
			'queue' => Expect::string()->required(),
			'bulkSize' => Expect::int(1)->min(1),
			'priorities' => Expect::arrayOf(Expect::int()->min(1)->max(999))->default([1])->mergeDefaults(false),
			'connection' => Expect::string(),
			'tableName' => Expect::string('background_job'),
			'producer' => Expect::string()->nullable(),
			'waitingJobExpiration' => Expect::int(1000),
			'logger'=> Expect::anyOf(Expect::type(\Nette\DI\Definitions\Statement::class),  Expect::type(\Nette\DI\Statement::class))->nullable(),
			'onBeforeProcess' => Expect::type('callable')->nullable(),
			'onError' => Expect::type('callable')->nullable(),
			'onAfterProcess' => Expect::type('callable')->nullable(),
			'onProcessingGetMetadata' => Expect::type('callable')->nullable(),
			'parametersFormat' => Expect::string()->nullable(),
			'autoUpdateSchema' => Expect::bool(true)->required(),
		]);
	}

	public function loadConfiguration(): void
	{
		// nette/di 2.4
		$this->config = $config = $this->objectToArray((new Processor)->process($this->getConfigSchema(), $this->config));
		$builder = $this->getContainerBuilder();

		foreach ($config['callbacks'] as $callbackName => $callbackData) {
			if (!isset($callbackData['callback'])) {
				// structure unification:
				// from: callbackName => callback
				// to: callbackName => callback + queue

				$config['callbacks'][$callbackName] = [
					'callback' => $callbackData,
					'queue' => null,
					'priority' => null,
				];
			}
		}

		// service registration

		$builder->addDefinition($this->prefix('service'))
			->setFactory(BackgroundQueue::class)
			->setArguments(['config' => $config]);

		// command registration

		$defs[] = $builder->addDefinition($this->prefix('clearFinishedCommand'))
			->setFactory(ClearFinishedCommand::class)
			->setAutowired(false);

		$defs[] = $builder->addDefinition($this->prefix('processCommand'))
			->setFactory(ProcessCommand::class)
			->setAutowired(false);

		if ($config['producer']) {
			$builder->addDefinition($this->prefix('consumeCommand'))
				->setFactory(ConsumeCommand::class)
				->setAutowired(false);

			$defs[] = $builder->addDefinition($this->prefix('reloadConsumerCommand'))
				->setFactory(ReloadConsumersCommand::class)
				->setAutowired(false);
		}

		$defs[] = $builder->addDefinition($this->prefix('updateSchemaCommand'))
			->setFactory(UpdateSchemaCommand::class)
			->setAutowired(false);

		foreach ($defs as $_def) {
			$_def->addSetup('setLocksDir', [$config['locksDir']]);
		}
	}

	private function objectToArray($array)
	{
		if (is_array($array)) {
			foreach ($array as $key => $value) {
				if (is_array($value)) {
					$array[$key] = $this->objectToArray($value);
				}
				if ($value instanceof stdClass) {
					$array[$key] = $this->objectToArray((array)$value);
				}
			}
		}
		if ($array instanceof stdClass) {
			return $this->objectToArray((array)$array);
		}
		return $array;
	}
}
