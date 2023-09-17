# Background Queue for Nette using RabbitMQ

## 1 Installation & Configuration

### 1.1 Installation

```
composer require adt/background-queue-nette
```

### 1.2 Configuration

```neon
parameters:
	database:
		serverVersion: '8.0'
		driver: pdo_mysql
		host: %env.DB_HOST%
		port: %env.DB_PORT%
		user: %env.DB_USER%
		password: %env.DB_PASSWORD%
		dbname: %env.DB_DBNAME%

	backgroundQueue:
		queue: %env.PROJECT_NAME%

extensions:
	backgroundQueue: ADT\BackgroundQueueNette\DI\BackgroundQueueExtension

backgroundQueue:
	callbacks:
		sendEmail: [@App\Model\Mailer, sendEmail]
		sendEmail2: # možnost specifikace jiné fronty pro tento callback
			callback: [@App\Model\Mailer, sendEmail]
			queue: some_other_queue
	notifyOnNumberOfAttempts: 5 # počet pokusů o zpracování záznamu před zalogováním
	tempDir: %tempDir% # cesta pro uložení informace, že byla provedena aktualizace databázové struktury
	locksDir: %locksDir% # cesta pro uložení zámku proti vícenásobnému spuštění commandů
	connection: %database% # parametry predavane do Doctrine\Dbal\Connection
	queue: %backgroundQueue.queue% # název fronty, do které se ukládají a ze které se vybírají záznamy
	tableName: background_job # nepovinné, název tabulky, do které se budou ukládat jednotlivé joby
	logger: Tracy\Bridges\Psr\TracyToPsrLoggerAdapter(\Tracy\Debugger::getLogger()) # nepovinné, musí implementovat psr/log LoggerInterface
	onBeforeProcess: null # nepovinné
	onError: [ADT\Utils\Guzzle, handleException]  # nepovinné
	onAfterProcess: null # nepovinné
```

## 1.3 Broker (optional)

### 1.3.1 Installation

https://github.com/AppsDevTeam/background-queue#131-php-amqplib-installation

### 1.3.2 Configuration

```neon
parameters:
	backgroundQueue:
		...
		broker:
			connection:
				host: %env.BROKER_HOST%
				port: %env.BROKER_PORT%
				user: %env.BROKER_USER%
				password: %env.BROKER_PASSWORD%
			queue:
				arguments: {'x-queue-type': ['S', 'quorum']} 

services:
	backgroundQueue.broker.manager: ADT\BackgroundQueue\Broker\PhpAmqpLib\Manager(%backgroundQueue.broker.connection%, %backgroundQueue.broker.queue%)
	backgroundQueue.broker.producer: ADT\BackgroundQueue\Broker\PhpAmqpLib\Producer
	backgroundQueue.broker.consumer: ADT\BackgroundQueue\Broker\PhpAmqpLib\Consumer

backgroundQueue:
	...
	producer: @backgroundQueue.broker.producer
	waitingJobExpiration: 1000
```

## 2. Usage

https://github.com/AppsDevTeam/background-queue#2-pou%C5%BEit%C3%AD
