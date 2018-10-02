<?php
declare(strict_types=1);
namespace Pccomponentes\DddMongoDb;

use Pccomponentes\Ddd\Domain\Model\Snapshot;

interface SnapshotUnserializer
{
    public function unserialize(array $snapshot): Snapshot;
}
