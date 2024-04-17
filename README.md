
## Configuration
Add connection to config/queue.php:
```
'connections' => [
    'rabbitmq' => [
            'driver' => 'rabbitmq',
            'queue' => env('RABBITMQ_QUEUE', 'default'),
            'connection' => 'default',
            'worker' => env('RABBITMQ_WORKER', 'horizon'),
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

`'job' => \App\RabbitMq\JobHandler::class` class that response for consuming messages 

Example of `App\RabbitMq\JobHandler` class

```
use App\RabbitMq\Consumers\ConsumedMessageTypeFactory;
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
        } else {
            if (!empty($payload['msgType'])) {
                $messageType = ConsumedMessageTypeFactory::create($payload['msgType']);
                if ($messageType) {
                    $this->instance = $messageType;
                    $messageType->handle($this);
                } else {
                    Log::error('Unknown message type ' . $payload['msgType']);
                }
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

Configuration file 'rabbitmq_system.php' contains information about producers and consumers. Example:

```
use App\RabbitMq\Producers\ProducedMessageTypeCode;
use App\RabbitMq\ConsumedMessageTypeCode;
use App\RabbitMq\Consumers\ProductQuestionReadByManager;
use App\RabbitMq\Consumers\ProductQuestionAnswerReadByManager;

return [
    'producers' => [
        ProducedMessageTypeCode::Product_viewed->value => [
            'exchange' => [
                'name' => 'products.product.viewed.direct_exchange',
                'type' => 'direct',
                'routing_key' => 'product.viewed'
            ],
            'horizon_queue' => null
        ]
    ],
    'consumers' => [
        ConsumedMessageTypeCode::Product_question_read_by_manager->value => [
            'handler' => new ProductQuestionReadByManager()
        ],
        ConsumedMessageTypeCode::Product_question_answer_read_by_manager->value => [
            'handler' => new ProductQuestionAnswerReadByManager()
        ],
    ]
];
```

App\RabbitMq\Producers\ProducedMessageTypeCode - produced messages codes 

```
namespace App\DomainCore\RabbitMq\Producers;

enum ProducedMessageTypeCode: string
{
    case Product_viewed = 'product_viewed';
}
```

App\RabbitMq\ConsumedMessageTypeCode - consumed messages codes

```
namespace App\DomainCore\RabbitMq;

enum ConsumedMessageTypeCode: string
{
    case Product_question_read_by_manager = 'product_question_read_by_manager';
    case Product_question_answer_read_by_manager = 'product_question_answer_read_by_manager';
}

```

## Usage between different servers

To consume a message you should create a consumer class that implements `VantukhKolya\RabbitMqClient\Core\MessageTypeInterface` interface.
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

Produce message to RabbitMq:

```
use App\DomainCore\RabbitMq\Producers\ProducedMessageTypeCode;
use App\DomainCore\RabbitMq\Producers\ProducerInfo;
use App\Events\ProductViewed;
use VantukhKolya\RabbitMqClient\Core\Message;
use VantukhKolya\RabbitMqClient\Core\RabbitMqProducer;

$rabbitMqMsq = new Message(
    ProducedMessageTypeCode::Product_viewed->value,
    ['product_code' => $event->productCode]
);
$producerInfo = ProducerInfo::get(ProducedMessageTypeCode::Product_viewed);
RabbitMqProducer::push($rabbitMqMsq, $producerInfo['exchange'], $producerInfo['horizon_queue'])
```

