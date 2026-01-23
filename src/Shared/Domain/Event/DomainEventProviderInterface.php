<?php

namespace App\Shared\Domain\Event;

interface DomainEventProviderInterface
{
    public function raiseDomainEvent(AbstractDomainEvent $event): void;

    /**
     * @return array<int, AbstractDomainEvent>
     */
    public function releaseDomainEvents(): array;
}
