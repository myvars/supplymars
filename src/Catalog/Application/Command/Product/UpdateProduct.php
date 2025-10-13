<?php

namespace App\Catalog\Application\Command\Product;

use App\Catalog\Domain\Model\Category\CategoryId;
use App\Catalog\Domain\Model\Manufacturer\ManufacturerId;
use App\Catalog\Domain\Model\Product\ProductPublicId;
use App\Catalog\Domain\Model\Subcategory\SubcategoryId;

final readonly class UpdateProduct
{
    public function __construct(
        public ProductPublicId $id,
        public string $name,
        public ?string $description,
        public CategoryId $categoryId,
        public SubcategoryId $subcategoryId,
        public ManufacturerId $manufacturerId,
        public string $mfrPartNumber,
        public ?int $ownerId,
        public bool $isActive,
    ) {
    }
}
