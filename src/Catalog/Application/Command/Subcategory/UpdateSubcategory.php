<?php

namespace App\Catalog\Application\Command\Subcategory;

use App\Catalog\Domain\Model\Category\CategoryId;
use App\Catalog\Domain\Model\Subcategory\SubcategoryPublicId;
use App\Shared\Domain\ValueObject\PriceModel;

final readonly class UpdateSubcategory
{
    public function __construct(
        public SubcategoryPublicId $id,
        public string $name,
        public CategoryId $categoryId,
        public string $defaultMarkup,
        public PriceModel $priceModel,
        public ?int $ownerId,
        public bool $isActive,
    ) {
    }
}


