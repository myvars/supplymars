<?php

namespace App\Entity\Interfaces;

use App\Event\DomainEvent;

interface DomainEventProviderInterface
{
    public function raiseDomainEvent(DomainEvent $event): void;

    public function releaseDomainEvents(): array;
}