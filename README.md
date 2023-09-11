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
		password: %end.DB_PASSWORD%
		dbname: %emv.DB_DBNAME%
	rabbitMQ:
		connection:
			host: HOST
			user: USER
			password: PASSWORD
			name: NAME
		queue:
			arguments: {'x-queue-type': ['S', 'quorum']} 

extensions:
	backgroundQueue: ADT\BackgroundQueueNette\DI\BackgroundQueueExtension

services:
	rabbitMQ.connection: ADT\BackgroundQueue\Broker\PhpAmqpLib\Connection(%rabbitMQ.connection%)
	rabbitMQ.producer: ADT\BackgroundQueue\Broker\PhpAmqpLib\Producer(%rabbitMQ.queue%)
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
