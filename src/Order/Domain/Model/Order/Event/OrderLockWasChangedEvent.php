<?php

declare(strict_types=1);

namespace App\Order\Domain\Model\Order\Event;

use App\Customer\Domain\Model\User\UserPublicId;
use App\Order\Domain\Model\Order\OrderPublicId;
use App\Shared\Domain\Event\AbstractDomainEvent;
use App\Shared\Domain\Event\DomainEventType;

/** An order was locked, its lock released, or the lock moved to another holder. */
final class OrderLockWasChangedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly OrderPublicId $id,
        private readonly ?UserPublicId $previousHolderId,
        private readonly ?UserPublicId $holderId,
        private readonly UserPublicId $changedById,
    ) {
        parent::__construct(DomainEventType::ORDER_LOCK_CHANGED);
    }

    public function getId(): OrderPublicId
    {
        return $this->id;
    }

    public function getPreviousHolderId(): ?UserPublicId
    {
        return $this->previousHolderId;
    }

    /** Null when the lock was released. */
    public function getHolderId(): ?UserPublicId
    {
        return $this->holderId;
    }

    public function getChangedById(): UserPublicId
    {
        return $this->changedById;
    }

    /** A user lost the lock they held to someone else's action: released, or given to another holder. */
    public function isReleasedByAnotherUser(): bool
    {
        return $this->previousHolderId instanceof UserPublicId
            && !$this->previousHolderId->equals($this->changedById);
    }
}
