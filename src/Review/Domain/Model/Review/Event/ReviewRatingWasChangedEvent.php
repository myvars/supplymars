<?php

namespace App\Review\Domain\Model\Review\Event;

use App\Review\Domain\Model\Review\ReviewPublicId;
use App\Shared\Domain\Event\AbstractDomainEvent;
use App\Shared\Domain\Event\DomainEventType;

final class ReviewRatingWasChangedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly ReviewPublicId $id,
    ) {
        parent::__construct(DomainEventType::REVIEW_RATING_CHANGED);
    }

    public function getId(): ReviewPublicId
    {
        return $this->id;
    }
}
