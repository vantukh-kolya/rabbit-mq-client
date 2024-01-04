
## Configuration
Add connection to config/queue.php:
```
'connections' => [
    'rabbitmq' => [
            'driver' => 'rabbitmq',
            'queue' => env('RABBITMQ_QUEUE', 'default'),
            'connection' => 'default',
            'hosts' => [
                [
                    'host' => env('RABBITMQ_HOST', 'rabbitmq'),
                    'port' => env('RABBITMQ_PORT', 5672),
                    'user' => env('RABBITMQ_USER', 'guest'),
                    'password' => env('RABBITMQ_PASSWORD', 'guest'),
                    'vhost' => env('RABBITMQ_VHOST', '/'),
                ],
            ],
            'options' => [
                'queue' => [
                    'exchange' => env('RABBITMQ_EXCHANGE', 'products_microservice_default_direct'),
                    'exchange_type' => env('RABBITMQ_EXCHANGE_TYPE', 'direct'),
                    'exchange_routing_key' => env('RABBITMQ_EXCHANGE_ROUTING_KEY', 'products_microservice.default'),
                    'job' => \App\RabbitMq\JobHandler::class,
                ],
                'heartbeat' => 10,
            ]
    ]
]
```

`'job' => \App\RabbitMq\JobHandler::class` class that response for processing incoming messages 

Example of `App\RabbitMq\JobHandler` class

```
use App\RabbitMq\MessageTypes\MessageTypeFactory;
use Illuminate\Queue\Jobs\JobName;
use VantukhKolya\RabbitMqClient\Queue\Jobs\RabbitMQJob;
use Illuminate\Support\Facades\Log;

class JobHandler extends RabbitMQJob
{
    public function fire()
    {
        $payload = $this->payload();
        if (!empty($payload['job'])) {
            [$class, $method] = JobName::parse($payload['job']);
            ($this->instance = $this->resolve($class))->{$method}($this, $payload['data']);
        } else if (!empty($payload['type'])) {
            $messageType = MessageTypeFactory::create($payload['type']);
            if ($messageType) {
                $this->instance = $messageType;
                $messageType->handle($this);
            } else {
                Log::error('Unknown message type.');
            }
        }
        $this->delete();
    }

    protected function failed($e)
    {
        $payload = $this->payload();
        if (!empty($payload['job'])) {
            [$class, $method] = JobName::parse($payload['job']);
            if (method_exists($this->instance = $this->resolve($class), 'failed')) {
                $this->instance->failed($payload['data'], $e, $payload['uuid'] ?? '');
            }
        }
    }

}
```

## Usage between different servers

To consume a message you should create a Message class that implements `VantukhKolya\RabbitMqClient\Core\MessageTypeInterface` interface.
For example:
```
use VantukhKolya\RabbitMqClient\Core\MessageTypeInterface;
use VantukhKolya\RabbitMqClient\Queue\Jobs\RabbitMQJob;

class ProductViewed implements MessageTypeInterface
{
    public function handle(RabbitMQJob $job): void
    {
        $messagePayload = $job->payload();
        if (isset($messagePayload['data']['product_code'])) {
            $product = $this->entityManager->find(Product::class, $messagePayload['data']['product_code']);
            if ($product) {
                $product->incrementViewCount();
                $this->entityManager->flush();
            }
        }
    }
}

```
Listen for new messages via supervisor process
```
[program:process-viewed-product-rabbitmq-worker]
process_name=%(program_name)s_%(process_num)02d
command=php %(ENV_SITE_PATH)s/artisan queue:work rabbitmq  --queue=product_viewed_queue
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=admin
numprocs=1
redirect_stderr=true
stdout_logfile=%(ENV_SITE_PATH)s/storage/logs/worker.log
stopwaitsecs=3600
```

To send a message into exchange you should create a Exchange class that implements `VantukhKolya\RabbitMqClient\Core\ExchangeInterface` interface.
For example:
```
use VantukhKolya\RabbitMqClient\Core\ExchangeInterface;

class SeoCategoryUpdateDirect implements ExchangeInterface
{
    public function getExchangeName(): string
    {
        return 'seo_category_update_direct';
    }

    public function getExchangeType(): string
    {
        return 'direct';
    }

    public function getExchangeRoutingKey(): string
    {
        return 'seo_category_update';
    }

}

```

Push message to RabbitMq:

```
use VantukhKolya\RabbitMqClient\Core\Message;
use VantukhKolya\RabbitMqClient\Core\RabbitMqProducer;

$rabbitMqMsq = new Message('update_categories_seo_tags');
RabbitMqProducer::push($rabbitMqMsq, new SeoCategoryUpdateDirect());
```

