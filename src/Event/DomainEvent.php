<?php

namespace App\Event;

use App\Entity\User;
use App\Enum\DomainEventType;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\EventDispatcher\Event;

abstract class DomainEvent extends Event
{
    private readonly \DateTimeImmutable $eventTimestamp;

    public function __construct(
        private readonly DomainEventType $domainEventType,
        private ?User $user = null,
    ) {
        $this->eventTimestamp = new \DateTimeImmutable();
    }

    public function getDomainEventType(): DomainEventType
    {
        return $this->domainEventType;
    }

    public function getEventTimestamp(): \DateTimeImmutable
    {
        return $this->eventTimestamp;
    }

    public function setUser(?UserInterface $user): void
    {
        $this->user = $user;
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
