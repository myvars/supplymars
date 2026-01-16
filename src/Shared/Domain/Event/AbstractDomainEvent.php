<?php

namespace App\Shared\Domain\Event;

use Symfony\Contracts\EventDispatcher\Event;

abstract class AbstractDomainEvent extends Event implements DomainEventInterface
{
    private readonly \DateTimeImmutable $occurredAt;

    public function __construct(
        private readonly DomainEventType $type,
        ?\DateTimeImmutable $occurredAt = null,
    ) {
        $this->occurredAt = $occurredAt ?? new \DateTimeImmutable();
    }

    public function getOccurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function getType(): DomainEventType
    {
        return $this->type;
    }
}
