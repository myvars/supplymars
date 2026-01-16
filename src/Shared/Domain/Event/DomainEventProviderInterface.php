<?php

namespace App\Shared\Domain\Event;

interface DomainEventProviderInterface
{
    public function raiseDomainEvent(AbstractDomainEvent $event): void;

    public function releaseDomainEvents(): array;
}
