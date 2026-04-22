<?php

declare(strict_types=1);

namespace App\Shared\Domain\Event;

interface DomainEventProviderInterface
{
    public function raiseDomainEvent(AbstractDomainEvent $event): void;

    /**
     * @return array<int, AbstractDomainEvent>
     */
    public function releaseDomainEvents(): array;
}
