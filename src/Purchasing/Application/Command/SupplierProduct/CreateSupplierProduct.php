<?php

declare(strict_types=1);

namespace App\Purchasing\Application\Command\SupplierProduct;

use App\Purchasing\Domain\Model\Supplier\SupplierId;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierCategoryId;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierManufacturerId;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierSubcategoryId;

final readonly class CreateSupplierProduct
{
    /**
     * @param numeric-string $cost
     */
    public function __construct(
        public string $name,
        public string $productCode,
        public SupplierId $supplierId,
        public SupplierCategoryId $supplierCategoryId,
        public SupplierSubcategoryId $supplierSubcategoryId,
        public SupplierManufacturerId $supplierManufacturerId,
        public string $mfrPartNumber,
        public int $weight,
        public int $stock,
        public int $leadTimeDays,
        public string $cost,
        public ?int $productId,
        public bool $isActive,
    ) {
    }
}
