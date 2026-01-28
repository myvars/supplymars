<?php

namespace App\Review\Domain\Model\Review\Event;

use App\Review\Domain\Model\Review\ReviewPublicId;
use App\Shared\Domain\Event\AbstractDomainEvent;
use App\Shared\Domain\Event\DomainEventType;
use App\Shared\Domain\Event\StatusWasChangedEventInterface;
use App\Shared\Domain\ValueObject\StatusChange;

final class ReviewStatusWasChangedEvent extends AbstractDomainEvent implements StatusWasChangedEventInterface
{
    public function __construct(
        private readonly ReviewPublicId $id,
        private readonly StatusChange $statusChange,
    ) {
        parent::__construct(DomainEventType::REVIEW_STATUS_CHANGED);
    }

    public function getId(): ReviewPublicId
    {
        return $this->id;
    }

    public function getStatusChange(): StatusChange
    {
        return $this->statusChange;
    }
}
