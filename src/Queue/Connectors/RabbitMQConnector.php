<?php

namespace VantukhKolya\RabbitMqClient\Queue\Connectors;

use Exception;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Queue\Connectors\ConnectorInterface;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\WorkerStopping;
use VantukhKolya\RabbitMqClient\Queue\Connection\ConnectionFactory;
use VantukhKolya\RabbitMqClient\Queue\QueueFactory;
use VantukhKolya\RabbitMqClient\Queue\RabbitMQQueue;
use VantukhKolya\RabbitMqClient\Horizon\Listeners\RabbitMQFailedEvent;
use VantukhKolya\RabbitMqClient\Horizon\RabbitMQQueue as HorizonRabbitMQQueue;
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

        if ($queue instanceof HorizonRabbitMQQueue) {
            $this->dispatcher->listen(JobFailed::class, RabbitMQFailedEvent::class);
        }
        
        $this->dispatcher->listen(WorkerStopping::class, static function () use ($queue): void {
            $queue->close();
        });

        return $queue;
    }
}