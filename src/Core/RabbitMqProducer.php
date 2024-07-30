<?php

namespace VantukhKolya\RabbitMqClient\Core;

use Illuminate\Support\Facades\Queue;

class RabbitMqProducer
{
    public static function push(Message $message, ExchangeInterface $exchange = null, string $queue = null): void
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

        $queue = $queue ?? env('RABBITMQ_QUEUE');
        Queue::connection('rabbitmq')->pushRaw(json_encode($payload), $queue, $exchangeConf);
    }

    public static function pushWithNewConn(
        Message $message,
        ExchangeInterface $exchange = null,
        string $queue = null
    ): void {
        $rabbitmqQueue = Queue::connection('rabbitmq');
        $rabbitmqQueue->reconnect();
        self::push($message, $exchange, $queue);
        $rabbitmqQueue->close();
    }
}
