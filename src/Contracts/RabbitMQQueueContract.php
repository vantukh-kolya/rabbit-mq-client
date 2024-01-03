<?php

namespace VantukhKolya\RabbitMqClient\Contracts;

use PhpAmqpLib\Connection\AbstractConnection;
use VantukhKolya\RabbitMqClient\Queue\QueueConfig;
use VantukhKolya\RabbitMqClient\Queue\RabbitMQQueue;

interface RabbitMQQueueContract
{
    public function __construct(QueueConfig $config);

    public function setConnection(AbstractConnection $connection): RabbitMQQueue;
}