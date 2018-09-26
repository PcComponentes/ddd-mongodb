<?php
declare(strict_types=1);
namespace Pccomponentes\DddMongoDb;

use Pccomponentes\Ddd\Domain\Model\DomainEvent;
use Pccomponentes\Ddd\Domain\Model\ValueObject\DateTimeValueObject;
use Pccomponentes\Ddd\Domain\Model\ValueObject\Uuid;
use Pccomponentes\Ddd\Infrastructure\Repository\EventStoreRepository;

class MongoDbEventStoreRepository extends MongoDbBaseAggregateRepository implements EventStoreRepository
{
    public function add(DomainEvent ...$events): void
    {
        $this->insert(...$events);
    }

    public function get(Uuid $aggregateId): array
    {
        return $this->findByAggregateId($aggregateId);
    }

    public function getSince(Uuid $aggregateId, DateTimeValueObject $occurredOn): array
    {
        return $this->findByAggregateIdSince($aggregateId, $occurredOn);
    }
}
