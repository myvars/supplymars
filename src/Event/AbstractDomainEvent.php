<?php

namespace App\Event;

use App\Enum\DomainEventType;
use Symfony\Contracts\EventDispatcher\Event;

abstract class AbstractDomainEvent extends Event implements DomainEventInterface
{
    public function __construct(
        private readonly DomainEventType $type,
        private ?\DateTimeImmutable $occurredAt = null
    ) {
        $this->occurredAt = $occurredAt ?? new \DateTimeImmutable();
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function type(): DomainEventType
    {
        return $this->type;
    }
}
