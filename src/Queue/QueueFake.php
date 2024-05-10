<?php

namespace VantukhKolya\RabbitMqClient\Queue;

use Illuminate\Support\Traits\ReflectsClosures;
use Illuminate\Support\Testing\Fakes\QueueFake as BaseQueueFake;
use PHPUnit\Framework\Assert as PHPUnit;

class QueueFake extends BaseQueueFake
{
    use ReflectsClosures;

    protected $rabbitMqMessages = [];

    public function assertMessageProduced(string $producedMessageTypeCode, array $expectedData)
    {
        $produced = false;
        if (!empty($this->rabbitMqMessages)) {
            foreach ($this->rabbitMqMessages as $rabbitMqMessage) {
                $message = json_decode($rabbitMqMessage, true);
                if ($message['msgType'] === $producedMessageTypeCode) {
                    if ($message['msgType'] === $producedMessageTypeCode) {
                        if (empty($expectedData)) {
                            $produced = true;
                        } else {
                            $allMatch = true;
                            foreach ($expectedData as $key => $value) {
                                if (!isset($message['data'][$key]) || $message['data'][$key] !== $value) {
                                    $allMatch = false;
                                    break;
                                }
                            }
                            if ($allMatch) {
                                $produced = true;
                                break;
                            }
                        }
                    }
                }
            }
        }

        PHPUnit::assertTrue(
            $produced,
            "The expected [{$producedMessageTypeCode}] message was not pushed."
        );
    }

    /**
     * Push a raw payload onto the queue.
     *
     * @param string $payload
     * @param string|null $queue
     * @param array $options
     * @return mixed
     */
    public function pushRaw($payload, $queue = null, array $options = [])
    {
        $this->rabbitMqMessages[] = $payload;
    }


}
