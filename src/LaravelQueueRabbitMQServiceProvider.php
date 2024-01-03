<?php

namespace VantukhKolya\RabbitMqClient;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Queue\QueueManager;
use Illuminate\Support\ServiceProvider;
use VantukhKolya\RabbitMqClient\Console\ConsumeCommand;
use VantukhKolya\RabbitMqClient\Console\ExchangeDeclareCommand;
use VantukhKolya\RabbitMqClient\Console\ExchangeDeleteCommand;
use VantukhKolya\RabbitMqClient\Console\QueueBindCommand;
use VantukhKolya\RabbitMqClient\Console\QueueDeclareCommand;
use VantukhKolya\RabbitMqClient\Console\QueueDeleteCommand;
use VantukhKolya\RabbitMqClient\Console\QueuePurgeCommand;
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
                ConsumeCommand::class,
            ]);
        }

        $this->commands([
            ExchangeDeclareCommand::class,
            ExchangeDeleteCommand::class,
            QueueBindCommand::class,
            QueueDeclareCommand::class,
            QueueDeleteCommand::class,
            QueuePurgeCommand::class,
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