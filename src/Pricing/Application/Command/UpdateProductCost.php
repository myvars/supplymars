<?php

declare(strict_types=1);

namespace App\Pricing\Application\Command;

use App\Catalog\Domain\Model\Product\ProductPublicId;
use App\Shared\Domain\ValueObject\PriceModel;

final readonly class UpdateProductCost
{
    /**
     * @param numeric-string $defaultMarkup
     */
    public function __construct(
        public ProductPublicId $id,
        public string $defaultMarkup,
        public PriceModel $priceModel,
        public bool $isActive,
    ) {
    }
}
