<?php
declare(strict_types=1);
namespace Pccomponentes\DddMongoDb;

use Pccomponentes\Ddd\Domain\Model\DomainEvent;

interface DomainEventUnserializer
{
    public function unserialize(array $event): DomainEvent;
}
