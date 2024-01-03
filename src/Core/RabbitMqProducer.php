<?php

namespace VantukhKolya\RabbitMqClient\Core;

use Illuminate\Support\Facades\Queue;

class RabbitMqProducer
{
    public static function push(Message $message, ExchangeInterface $exchange = null): void
    {
        $exchangeConf = [];
        if ($exchange) {
            $exchangeConf = [
                'exchange' => $exchange->getExchangeName(),
                'exchange_type' => $exchange->getExchangeType(),
                'exchange_routing_key' => $exchange->getExchangeRoutingKey(),
            ];
        }
        Queue::connection('rabbitmq')->pushRaw(json_encode($message->getPayload()), env('RABBITMQ_QUEUE'), $exchangeConf);
    }
}