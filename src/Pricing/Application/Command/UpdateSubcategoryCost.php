<?php

namespace App\Pricing\Application\Command;

use App\Catalog\Domain\Model\Subcategory\SubcategoryPublicId;
use App\Shared\Domain\ValueObject\PriceModel;

final readonly class UpdateSubcategoryCost
{
    public function __construct(
        public SubcategoryPublicId $id,
        public string $defaultMarkup,
        public PriceModel $priceModel,
        public bool $isActive,
    ) {
    }
}
