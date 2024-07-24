<?php

namespace App\Entity\Traits;

use App\Event\DomainEvent;

trait DomainEventTrait
{
    private array $domainEvents = [];

    public function raiseDomainEvent(DomainEvent $event): void
    {
        $this->domainEvents[] = $event;
    }

    public function releaseDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];
        return $events;
    }
}