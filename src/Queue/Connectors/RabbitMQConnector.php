<?php

namespace VantukhKolya\RabbitMqClient\Queue\Connectors;

use Exception;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Queue\Connectors\ConnectorInterface;
use Illuminate\Queue\Events\WorkerStopping;
use VantukhKolya\RabbitMqClient\Queue\Connection\ConnectionFactory;
use VantukhKolya\RabbitMqClient\Queue\QueueFactory;
use VantukhKolya\RabbitMqClient\Queue\RabbitMQQueue;

class RabbitMQConnector implements ConnectorInterface
{
    protected Dispatcher $dispatcher;

    public function __construct(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Establish a queue connection.
     *
     * @return RabbitMQQueue
     *
     * @throws Exception
     */
    public function connect(array $config): Queue
    {
        $connection = ConnectionFactory::make($config);

        $queue = QueueFactory::make($config)->setConnection($connection);

        $this->dispatcher->listen(WorkerStopping::class, static function () use ($queue): void {
            $queue->close();
        });

        return $queue;
    }
}