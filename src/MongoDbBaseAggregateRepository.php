<?php
declare(strict_types=1);
namespace Pccomponentes\DddMongoDb;

use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection;
use Pccomponentes\Ddd\Domain\Model\ValueObject\DateTimeValueObject;
use Pccomponentes\Ddd\Domain\Model\ValueObject\Uuid;
use Pccomponentes\Ddd\Util\Message\AggregateMessage;

class MongoDbBaseAggregateRepository
{
    private $collection;
    private $unserializer;

    final public function __construct(
        Collection $collection,
        MessageUnserializer $unserializer
    ) {
        $this->collection = $collection;
        $this->unserializer = $unserializer;
    }

    protected function collection():Collection
    {
        return $this->collection;
    }

    protected function insert(AggregateMessage ...$messages): void
    {
        foreach ($messages as $message) {
            $this->collection->insertOne(
                [
                    'message_id' => $message->messageId()->value(),
                    'aggregate_id' => $message->aggregateId()->value(),
                    'name' => $message::messageName(),
                    'payload' => $message->messagePayload(),
                    'occurred_on' => new UTCDateTime($message->occurredOn()),
                    'version' => $message::messageVersion()
                ]
            );
        }
    }

    protected function forceInsert(AggregateMessage ...$messages): void
    {
        foreach ($messages as $message) {
            $this->collection->replaceOne(
                ['aggregate_id' => $message->aggregateId()->value()],
                [
                    'message_id' => $message->messageId()->value(),
                    'aggregate_id' => $message->aggregateId()->value(),
                    'name' => $message::messageName(),
                    'payload' => $message->messagePayload(),
                    'projected_on' => new UTCDateTime($message->occurredOn()),
                    'version' => $message::messageVersion()
                ],
                ['upsert' => true]
            );
        }
    }

    protected function findByAggregateId(Uuid $aggregateId): array
    {
        $messageCursor = $this
            ->collection
            ->find(['aggregate_id' => $aggregateId->value()]);

        $mapped = [];
        foreach ($messageCursor as $message) {
            $mapped[] = $this->unserializer->unserialize($this->toAssoc($message));
        }

        return $mapped;
    }

    protected function findOneByAggregateId(Uuid $aggregateId): ?AggregateMessage
    {
        $one = $this
            ->collection
            ->findOne(['aggregate_id' => $aggregateId->value()]);

        return $one ? $this->unserializer->unserialize($this->toAssoc($one)) : null;
    }

    protected function findByAggregateIdSince(Uuid $aggregateId, DateTimeValueObject $occurredOn): array
    {
        $messageCursor = $this
            ->collection
            ->find(
                [
                    'aggregate_id' => $aggregateId->value(),
                    'occurred_on' => ['$gt' => $occurredOn]
                ]
            );

        $mapped = [];
        foreach ($messageCursor as $message) {
            $mapped[] = $this->unserializer->unserialize($this->toAssoc($message));
        }

        return $mapped;
    }

    protected function delete(AggregateMessage ...$messages): void
    {
        foreach ($messages as $message) {
            $this->collection->deleteOne(['aggregate_id' => $message->aggregateId()->value()]);
        }
    }

    private function toAssoc($message): array
    {
        return \json_decode(\json_encode($message), true);
    }
}
