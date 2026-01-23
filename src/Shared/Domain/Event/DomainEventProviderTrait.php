<?php

namespace App\Shared\Domain\Event;

trait DomainEventProviderTrait
{
    /** @var array<int, AbstractDomainEvent> */
    private array $domainEvents = [];

    public function raiseDomainEvent(AbstractDomainEvent $event): void
    {
        $this->domainEvents[] = $event;
    }

    /**
     * @return array<int, AbstractDomainEvent>
     */
    public function releaseDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }
}
