<?php

namespace App\Entity;

use App\Event\DomainEvent;

interface DomainEventProviderInterface
{
    public function raiseDomainEvent(DomainEvent $event): void;

    public function releaseDomainEvents(): array;
}
