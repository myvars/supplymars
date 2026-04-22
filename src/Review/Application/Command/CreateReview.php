<?php

declare(strict_types=1);

namespace App\Review\Application\Command;

final readonly class CreateReview
{
    public function __construct(
        public int $customerId,
        public int $productId,
        public int $orderId,
        public int $rating,
        public ?string $title,
        public ?string $body,
    ) {
    }
}
