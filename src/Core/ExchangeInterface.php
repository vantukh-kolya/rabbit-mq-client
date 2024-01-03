<?php

namespace VantukhKolya\RabbitMqClient\Core;

interface ExchangeInterface
{
    public function getExchangeName(): string;

    public function getExchangeType(): string;

    public function getExchangeRoutingKey(): string;
}