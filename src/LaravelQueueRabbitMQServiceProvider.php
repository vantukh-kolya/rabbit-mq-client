<?php

namespace VantukhKolya\RabbitMqClient;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Queue\QueueManager;
use Illuminate\Support\ServiceProvider;
use VantukhKolya\RabbitMQClient\Console\ConsumeCommand;
use VantukhKolya\RabbitMqClient\Queue\Connectors\RabbitMQConnector;

class LaravelQueueRabbitMQServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     */
    public function register(): void
    {
        if ($this->app->runningInConsole()) {
            $this->app->singleton('rabbitmq.consumer', function () {
                $isDownForMaintenance = function () {
                    return $this->app->isDownForMaintenance();
                };

                return new Consumer(
                    $this->app['queue'],
                    $this->app['events'],
                    $this->app[ExceptionHandler::class],
                    $isDownForMaintenance
                );
            });

            $this->app->singleton(ConsumeCommand::class, static function ($app) {
                return new ConsumeCommand(
                    $app['rabbitmq.consumer'],
                    $app['cache.store']
                );
            });

            $this->commands([
                VantukhKolya\RabbitMQClient\Console\ConsumeCommand::class,
            ]);
        }

        $this->commands([
            VantukhKolya\RabbitMQClient\Console\ExchangeDeclareCommand::class,
            VantukhKolya\RabbitMQClient\Console\ExchangeDeleteCommand::class,
            VantukhKolya\RabbitMQClient\Console\QueueBindCommand::class,
            VantukhKolya\RabbitMQClient\Console\QueueDeclareCommand::class,
            VantukhKolya\RabbitMQClient\Console\QueueDeleteCommand::class,
            VantukhKolya\RabbitMQClient\Console\QueuePurgeCommand::class,
        ]);
    }

    /**
     * Register the application's event listeners.
     */
    public function boot(): void
    {
        /** @var QueueManager $queue */
        $queue = $this->app['queue'];

        $queue->addConnector('rabbitmq', function () {
            return new RabbitMQConnector($this->app['events']);
        });
    }
}