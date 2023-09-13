# Background Queue for Nette using RabbitMQ

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
		host: mysql
		port: 3306
		user: %env.DB_USER%
		password: %env.DB_PASSWORD%
		dbname: %env.DB_DBNAME%
	rabbitMQ:
		connection:
			host: %env.RABBITMQ_HOST%
			user: %env.RABBITMQ_USER%
			password: %env.RABBITMQ_PASSWORD%
		queue:
			arguments: {'x-queue-type': ['S', 'quorum']} 

extensions:
	backgroundQueue: ADT\BackgroundQueueNette\DI\BackgroundQueueExtension

services:
	rabbitMQ.manager: ADT\BackgroundQueue\Broker\PhpAmqpLib\Manager(%rabbitMQ.connection%, %rabbitMQ.queue%)
	rabbitMQ.producer: ADT\BackgroundQueue\Broker\PhpAmqpLib\Producer
	rabbitMQ.consumer: ADT\BackgroundQueue\Broker\PhpAmqpLib\Consumer

backgroundQueue:
	callbacks:
		sendEmail: [@App\Model\Mailer, sendEmail]
		sendEmail2: # možnost specifikace jiné fronty pro tento callback
			callback: [@App\Model\Mailer, sendEmail]
			queue: some_other_queue
	notifyOnNumberOfAttempts: 5 # počet pokusů o zpracování záznamu před zalogováním
	tempDir: %tempDir% # cesta pro uložení zámku proti vícenásobnému spuštění commandu
	connection: %database% # parametry predavane do Doctrine\Dbal\Connection
	queue: %env.PROJECT_NAME% # název fronty, do které se ukládají a ze které se vybírají záznamy
	tableName: background_job # nepovinné, název tabulky, do které se budou ukládat jednotlivé joby
	producer: @rabbitMQ.producer # nepovinné, callback, který publishne zprávu do brokera
	waitingJobExpiration: 1000 # nepovinné, délka v ms, po které se job pokusí znovu provést, když čeká na dokončení předchozího
	logger: Tracy\Bridges\Psr\TracyToPsrLoggerAdapter(\Tracy\Debugger::getLogger()) # nepovinné, musí implementovat psr/log LoggerInterface
	onBeforeProcess: [System, switchDatabase] # nepovinné
	onError: [ADT\Utils\Guzzle, handleException]  # nepovinné
	onAfterProcess: [System, switchDatabaseBack] # nepovinné
```

## 1.3 RabbitMQ (optional)

Because RabbitMQ is optional dependency, it doesn't check your installed version against the version with which this package was tested. That's why it's recommended to add

```json
{
  "conflict": {
    "php-amqplib/php-amqplib": "<3.0.0 || >=4.0.0"
  }
}
```

to your composer and then run:

```
composer require php-amqplib/php-amqplib
```

This make sures you avoid BC break when upgrading `php-amqplib/php-amqplib` in the future.

This version of `php-amqplib/php-amqplib` also need `ext-sockets`. You can add it to your Dockerfile like this:

```Dockerfile
docker-php-ext-install sockets
```


