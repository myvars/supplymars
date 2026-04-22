<?php

declare(strict_types=1);

namespace App\Review\Application\Command;

use App\Shared\Application\Search\FilterCommand;

final readonly class ReviewFilter extends FilterCommand
{
    public function __construct(
        ?string $query,
        string $sort,
        string $sortDirection,
        int $page,
        int $limit,
        public ?string $reviewStatus,
        public ?int $productId,
        public ?int $customerId,
        public ?int $rating,
    ) {
        parent::__construct($query, $sort, $sortDirection, $page, $limit);
    }
}
