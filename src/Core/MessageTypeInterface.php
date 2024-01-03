<?php

namespace VantukhKolya\RabbitMqClient\Core;

use VantukhKolya\RabbitMqClient\Queue\Jobs\RabbitMQJob;

interface MessageTypeInterface
{
    public function handle(RabbitMQJob $job): void;
}