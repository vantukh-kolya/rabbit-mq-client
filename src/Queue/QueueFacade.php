<?php

namespace VantukhKolya\RabbitMqClient\Queue;

use Illuminate\Support\Facades\Queue;
/**
 * @method static void assertMessageProduced(string $producedMessageTypeCode, array $expectedData)
 */
class QueueFacade extends Queue
{
    public static function fake($jobsToFake = [])
    {
        $actualQueueManager = static::isFake()
            ? parent::getFacadeRoot()->queue
            : parent::getFacadeRoot();

        return tap(new QueueFake(parent::getFacadeApplication(), $jobsToFake, $actualQueueManager), function ($fake) {
            parent::swap($fake);
        });
    }

}
