<?php

namespace App\Catalog\Application\Command\Product;

use App\Shared\Application\Search\FilterCommand;

final readonly class ProductFilter extends FilterCommand
{
    public function __construct(
        ?string $query,
        string $sort,
        string $sortDirection,
        int $page,
        int $limit,
        public ?string $mfrPartNumber,
        public ?int $categoryId,
        public ?int $subcategoryId,
        public ?int $manufacturerId,
        public ?bool $inStock,
    ) {
        parent::__construct($query, $sort, $sortDirection, $page, $limit);
    }
}
