<?php

declare(strict_types=1);

namespace App\Catalog\Application\Command\Subcategory;

use App\Shared\Application\Search\FilterCommand;

final readonly class SubcategoryFilter extends FilterCommand
{
    public function __construct(
        ?string $query,
        string $sort,
        string $sortDirection,
        int $page,
        int $limit,
        public ?int $categoryId,
        public ?string $priceModel,
        public ?int $managerId,
    ) {
        parent::__construct($query, $sort, $sortDirection, $page, $limit);
    }
}
