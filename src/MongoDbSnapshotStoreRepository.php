<?php
declare(strict_types=1);
namespace Pccomponentes\DddMongoDb;

use Pccomponentes\Ddd\Domain\Model\Snapshot;
use Pccomponentes\Ddd\Domain\Model\ValueObject\Uuid;
use Pccomponentes\Ddd\Infrastructure\Repository\SnapshotStoreRepository;

class MongoDbSnapshotStoreRepository extends MongoDbBaseAggregateRepository implements SnapshotStoreRepository
{
    public function add(Snapshot $snapshot): void
    {
        $this->insert($snapshot);
    }

    public function get(Uuid $aggregateId): ?Snapshot
    {
        return $this->findOneByAggregateId($aggregateId);
    }

    public function remove(Snapshot $snapshot): void
    {
        $this->delete($snapshot);
    }
}
