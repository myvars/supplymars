<?php

namespace App\Shared\Domain\Event;

interface DomainEventInterface
{
    public function getType(): DomainEventType;

    public function getOccurredAt(): \DateTimeImmutable;
}
