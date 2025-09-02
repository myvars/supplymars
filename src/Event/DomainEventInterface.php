<?php

namespace App\Event;

use App\Enum\DomainEventType;

interface DomainEventInterface
{
    public function type(): DomainEventType;
    public function occurredAt(): \DateTimeImmutable;
}
