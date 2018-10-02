<?php
declare(strict_types=1);
namespace Pccomponentes\DddMongoDb;

use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection;
use Pccomponentes\Ddd\Domain\Model\Snapshot;
use Pccomponentes\Ddd\Domain\Model\ValueObject\Uuid;
use Pccomponentes\Ddd\Infrastructure\Repository\SnapshotStoreRepository;

class MongoDbSnapshotStoreRepository implements SnapshotStoreRepository
{
    private $collection;
    private $unserializer;

    final public function __construct(
        Collection $collection,
        SnapshotUnserializer $unserializer
    ) {
        $this->collection = $collection;
        $this->unserializer = $unserializer;
    }

    public function set(Snapshot $snapshot): void
    {
        $this->collection->replaceOne(
            ['aggregate_id' => $snapshot->aggregateId()->value()],
            [
                'message_id' => $snapshot->messageId()->value(),
                'aggregate_id' => $snapshot->aggregateId()->value(),
                'name' => $snapshot::messageName(),
                'payload' => $snapshot->messagePayload(),
                'projected_on' => new UTCDateTime($snapshot->occurredOn()),
                'version' => $snapshot::messageVersion()
            ],
            ['upsert' => true]
        );
    }

    public function get(Uuid $aggregateId): ?Snapshot
    {
        $one = $this
            ->collection
            ->findOne(['aggregate_id' => $aggregateId->value()]);

        return $one ? $this->unserializer->unserialize($this->toAssoc($one)) : null;
    }

    public function remove(Snapshot $snapshot): void
    {
        $this->collection->deleteOne(['aggregate_id' => $snapshot->aggregateId()->value()]);
    }

    private function toAssoc($message): array
    {
        return \json_decode(\json_encode($message), true);
    }
}
