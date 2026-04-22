<?php

declare(strict_types=1);

namespace App\Purchasing\Application\Command\SupplierProduct;

use App\Shared\Application\Search\FilterCommand;

final readonly class SupplierProductFilter extends FilterCommand
{
    public function __construct(
        ?string $query,
        string $sort,
        string $sortDirection,
        int $page,
        int $limit,
        public ?int $supplierId,
        public ?string $productCode,
        public ?int $supplierCategoryId,
        public ?int $supplierSubcategoryId,
        public ?int $supplierManufacturerId,
        public ?bool $inStock,
        public ?bool $isActive,
    ) {
        parent::__construct($query, $sort, $sortDirection, $page, $limit);
    }
}
