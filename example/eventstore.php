<?php
declare(strict_types=1);
require_once __DIR__ . '/../vendor/autoload.php';

use Pccomponentes\Ddd\Domain\Model\DomainEvent;
use Pccomponentes\DddMongoDb\MongoDbEventStoreRepository;
use Pccomponentes\DddMongoDb\DomainEventUnserializer;
use Pccomponentes\Ddd\Domain\Model\ValueObject\Uuid;
use Pccomponentes\Ddd\Domain\Model\ValueObject\DateTimeValueObject;
use \MongoDB\Client as MongoDBClient;

class ExampleEvent extends DomainEvent
{
    public function count(): int
    {
        return $this->messagePayload()['count'];
    }

    protected function assertPayload(): void
    {
        if (false === \array_key_exists('count', $this->messagePayload())) {
            throw new \InvalidArgumentException('Need count');
        }
    }

    public static function messageName(): string
    {
        return 'example';
    }

    public static function messageVersion(): string
    {
        return 'v1';
    }

    public static function create(Uuid $aggregateId, int $count): self
    {
        return new static(
            Uuid::v4(),
            $aggregateId,
            DateTimeValueObject::from('now'),
            [
                'count' => $count
            ]
        );
    }
}

class EventUnserializer implements DomainEventUnserializer
{
    public function unserialize(array $message): DomainEvent
    {
        $timestamp = \intdiv((int) $message['occurred_on']['$date']['$numberLong'], 1000);
        return ExampleEvent::fromPayload(
            Uuid::from($message['message_id']),
            Uuid::from($message['aggregate_id']),
            DateTimeValueObject::fromTimestamp($timestamp),
            $message['payload']
        );
    }
}

$client = new MongoDBClient('mongodb://user:root@mongo');
$aggregateId = Uuid::v4();
$repository = new MongoDbEventStoreRepository(
    $client->selectCollection('example', 'event_store'),
    new EventUnserializer()
);

for ($i = 0; $i < 3; $i++) {
    $repository->add(ExampleEvent::create($aggregateId, $i));
    \sleep(1);
}

$events = $repository->get($aggregateId);
dump($events);