<?php

namespace App\Entity;

use App\Event\AbstractDomainEvent;

trait DomainEventProviderTrait
{
    private array $domainEvents = [];

    public function raiseDomainEvent(AbstractDomainEvent $event): void
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
