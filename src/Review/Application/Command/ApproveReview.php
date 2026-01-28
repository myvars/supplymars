<?php

namespace App\Review\Application\Command;

use App\Review\Domain\Model\Review\ReviewPublicId;

final readonly class ApproveReview
{
    public function __construct(
        public ReviewPublicId $id,
    ) {
    }
}
