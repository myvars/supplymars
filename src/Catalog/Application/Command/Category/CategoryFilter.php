<?php

declare(strict_types=1);

namespace App\Catalog\Application\Command\Category;

use App\Shared\Application\Search\FilterCommand;

final readonly class CategoryFilter extends FilterCommand
{
    public function __construct(
        ?string $query,
        string $sort,
        string $sortDirection,
        int $page,
        int $limit,
        public ?string $priceModel,
        public ?int $managerId,
        public ?int $vatRateId,
    ) {
        parent::__construct($query, $sort, $sortDirection, $page, $limit);
    }
}
