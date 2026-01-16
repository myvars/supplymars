<?php

namespace App\Shared\Domain\Event;

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
