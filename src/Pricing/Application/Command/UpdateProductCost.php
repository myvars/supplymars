<?php

namespace App\Pricing\Application\Command;

use App\Catalog\Domain\Model\Product\ProductPublicId;
use App\Shared\Domain\ValueObject\PriceModel;

final readonly class UpdateProductCost
{
    public function __construct(
        public ProductPublicId $id,
        public string $defaultMarkup,
        public PriceModel $priceModel,
        public bool $isActive,
    ) {
    }
}
