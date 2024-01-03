<?php

namespace VantukhKolya\RabbitMqClient\Queue;

use Illuminate\Support\Arr;

class QueueFactory
{
    public static function make(array $config = []): RabbitMQQueue
    {
        $queueConfig = QueueConfigFactory::make($config);
        $worker = Arr::get($config, 'worker', 'default');

        if (strtolower($worker) == 'default') {
            return new RabbitMQQueue($queueConfig);
        }

        return new $worker($queueConfig);
    }
}