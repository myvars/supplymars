<?php

declare(strict_types=1);

namespace App\Review\Domain\Model\Review\Event;

use App\Catalog\Domain\Model\Product\ProductPublicId;
use App\Review\Domain\Model\Review\ReviewStatus;
use App\Shared\Domain\Event\AbstractDomainEvent;
use App\Shared\Domain\Event\DomainEventType;

/**
 * Carries the product and status because the review no longer exists when listeners run.
 */
final class ReviewWasDeletedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly ProductPublicId $productId,
        private readonly ReviewStatus $status,
    ) {
        parent::__construct(DomainEventType::REVIEW_DELETED);
    }

    public function getProductId(): ProductPublicId
    {
        return $this->productId;
    }

    public function getStatus(): ReviewStatus
    {
        return $this->status;
    }
}
