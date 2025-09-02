<?php

namespace App\Entity;

use App\Event\AbstractDomainEvent;

interface DomainEventProviderInterface
{
    public function raiseDomainEvent(AbstractDomainEvent $event): void;

    public function releaseDomainEvents(): array;
}
