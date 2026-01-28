<?php

namespace App\Review\Application\Command;

use App\Review\Domain\Model\Review\RejectionReason;
use App\Review\Domain\Model\Review\ReviewPublicId;

final readonly class RejectReview
{
    public function __construct(
        public ReviewPublicId $id,
        public RejectionReason $reason,
        public ?string $notes,
    ) {
    }
}
