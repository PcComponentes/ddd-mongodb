<?php
declare(strict_types=1);
namespace Pccomponentes\DddMongoDb;

use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection;
use Pccomponentes\Ddd\Domain\Model\DomainEvent;
use Pccomponentes\Ddd\Domain\Model\ValueObject\DateTimeValueObject;
use Pccomponentes\Ddd\Domain\Model\ValueObject\Uuid;
use Pccomponentes\Ddd\Infrastructure\Repository\EventStoreRepository;

class MongoDbEventStoreRepository implements EventStoreRepository
{
    private $collection;
    private $unserializer;

    public function __construct(
        Collection $collection,
        DomainEventUnserializer $unserializer
    ) {
        $this->collection = $collection;
        $this->unserializer = $unserializer;
    }

    public function add(DomainEvent ...$events): void
    {
        foreach ($events as $event) {
            $this->collection->insertOne(
                [
                    'message_id' => $event->messageId()->value(),
                    'aggregate_id' => $event->aggregateId()->value(),
                    'name' => $event::messageName(),
                    'payload' => $event->messagePayload(),
                    'occurred_on' => new UTCDateTime($event->occurredOn()),
                    'version' => $event::messageVersion()
                ]
            );
        }
    }

    public function get(Uuid $aggregateId): array
    {
        return $this->find('aggregate_id', $aggregateId->value());
    }

    public function getSince(Uuid $aggregateId, DateTimeValueObject $occurredOn): array
    {
        return $this->findSince('aggregate_id', $aggregateId->value(), $occurredOn);
    }

    public function getByMessageId(Uuid $messageId): ?DomainEvent
    {
        $this->findOne('message_id', $messageId->value());
    }

    public function getByMessageName(string $messageName): array
    {
        return $this->find('name', $messageName);
    }

    public function getByMessageNameSince(string $messageName, DateTimeValueObject $since): array
    {
        return $this->findSince('name', $messageName, $since);
    }

    protected function collection(): Collection
    {
        return $this->collection;
    }

    protected function unserializer(): DomainEventUnserializer
    {
        return $this->unserializer;
    }

    private function find(string $field, $value): array
    {
        $messageCursor = $this
            ->collection
            ->find(
                [$field => $value],
                ['sort' => ['occurred_on' => 1]]
            );

        $mapped = [];
        foreach ($messageCursor as $message) {
            $mapped[] = $this->unserializer->unserialize($this->toAssoc($message));
        }

        return $mapped;
    }

    private function findOne(string $field, $value): ?DomainEvent
    {
        $one = $this
            ->collection
            ->findOne([$field => $value]);

        return $one ? $this->unserializer->unserialize($this->toAssoc($one)) : null;
    }

    private function findSince(string $field, $value, DateTimeValueObject $since): array
    {
        $messageCursor = $this
            ->collection
            ->find(
                [
                    $field => $value,
                    'occurred_on' => ['$gt' => $since]
                ],
                ['sort' => ['occurred_on' => 1]]
            );

        $mapped = [];
        foreach ($messageCursor as $message) {
            $mapped[] = $this->unserializer->unserialize($this->toAssoc($message));
        }

        return $mapped;
    }

    protected function toAssoc($message): array
    {
        return \json_decode(\json_encode($message), true);
    }
}
