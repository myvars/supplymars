<?php

declare(strict_types=1);

namespace App\Review\Application\Command;

use App\Review\Domain\Model\Review\ReviewPublicId;

final readonly class HideReview
{
    public function __construct(
        public ReviewPublicId $id,
    ) {
    }
}
