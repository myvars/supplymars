<?php

namespace App\Catalog\Application\Command\Category;

use App\Shared\Domain\ValueObject\PriceModel;

final readonly class CreateCategory
{
    /**
     * @param numeric-string $defaultMarkup
     */
    public function __construct(
        public string $name,
        public int $vatRateId,
        public string $defaultMarkup,
        public PriceModel $priceModel,
        public int $ownerId,
        public bool $isActive,
    ) {
    }
}
