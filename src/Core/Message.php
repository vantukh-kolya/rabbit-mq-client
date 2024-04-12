<?php

namespace VantukhKolya\RabbitMqClient\Core;

use Ramsey\Uuid\Uuid;

class Message
{
    private string $uuid;
    private string $timestamp;
    private string $msgType;
    private array $data;

    public function __construct(string $msgType, array $data = [])
    {
        $this->msgType = $msgType;
        $this->uuid = Uuid::uuid4()->toString();
        $this->data = $data;
        $this->setTimestamp();
    }

    private function setTimestamp(): void
    {
        $date = new \DateTime("now", new \DateTimeZone('Europe/Kiev'));
        $this->timestamp = $date->format(\DateTime::ATOM);
    }

    public function getPayload(
        int $maxTries = null,
        bool $failOnTimeout = false,
        int $timeout = null
    ): array {
        return [
            'uuid' => $this->uuid,
            'displayName' => $this->msgType,
            'msgType' => $this->msgType,
            'job' => null,
            'maxTries' => $maxTries,
            'maxExceptions' => null,
            'failOnTimeout' => $failOnTimeout,
            'backoff' => null,
            'timeout' => $timeout,
            'data' => $this->data,
            'timestamp' => $this->timestamp
        ];
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getTimestamp(): string
    {
        return $this->timestamp;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getData(): array
    {
        return $this->data;
    }
}