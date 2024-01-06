<?php

namespace VantukhKolya\RabbitMqClient\Core;

use Illuminate\Support\Facades\Queue;

class RabbitMqProducer
{
    public static function push(Message $message, ExchangeInterface $exchange = null): void
    {
        $payload = $message->getPayload();
        $exchangeConf = [];
        if ($exchange) {
            $exchangeConf = [
                'exchange' => $exchange->getExchangeName(),
                'exchange_type' => $exchange->getExchangeType(),
                'exchange_routing_key' => $exchange->getExchangeRoutingKey(),
            ];
            $payload = array_merge($payload, $exchangeConf);
        }
        Queue::connection('rabbitmq')->pushRaw(json_encode($payload), env('RABBITMQ_QUEUE'), $exchangeConf);
    }
}
