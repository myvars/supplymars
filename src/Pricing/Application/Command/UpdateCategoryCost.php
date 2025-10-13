<?php

namespace App\Pricing\Application\Command;

use App\Catalog\Domain\Model\Category\CategoryPublicId;
use App\Shared\Domain\ValueObject\PriceModel;

final readonly class UpdateCategoryCost
{
    public function __construct(
        public CategoryPublicId $id,
        public string $defaultMarkup,
        public PriceModel $priceModel,
        public bool $isActive,
    ) {
    }
}

