<?php
declare(strict_types=1);
require_once __DIR__ . '/../vendor/autoload.php';

use Pccomponentes\Ddd\Domain\Model\Snapshot;
use Pccomponentes\DddMongoDb\MongoDbSnapshotStoreRepository;
use Pccomponentes\DddMongoDb\SnapshotUnserializer as BaseSnapshotUnserializer;
use Pccomponentes\Ddd\Domain\Model\ValueObject\Uuid;
use Pccomponentes\Ddd\Domain\Model\ValueObject\DateTimeValueObject;
use \MongoDB\Client as MongoDBClient;

class ExampleSnapshot extends Snapshot
{
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

class SnapshotUnserializer implements BaseSnapshotUnserializer
{
    public function unserialize(array $message): Snapshot
    {
        $timestamp = \intdiv((int) $message['occurred_on']['$date']['$numberLong'], 1000);
        return ExampleSnapshot::fromPayload(
            Uuid::from($message['message_id']),
            Uuid::from($message['aggregate_id']),
            DateTimeValueObject::fromTimestamp($timestamp),
            $message['payload']
        );
    }
}

$client = new MongoDBClient('mongodb://user:root@mongo');
$aggregateId = Uuid::v4();
$repository = new MongoDbSnapshotStoreRepository(
    $client->selectCollection('example', 'event_store'),
    new SnapshotUnserializer()
);


$repository->set(ExampleSnapshot::create($aggregateId, 1));

$snapshot = $repository->get($aggregateId);
dump($snapshot);