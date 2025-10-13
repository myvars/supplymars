<?php

namespace App\Catalog\Application\Command\Category;

use App\Catalog\Domain\Model\Category\CategoryPublicId;
use App\Shared\Domain\ValueObject\PriceModel;

final readonly class UpdateCategory
{
    public function __construct(
        public CategoryPublicId $id,
        public string $name,
        public int $vatRateId,
        public string $defaultMarkup,
        public PriceModel $priceModel,
        public int $ownerId,
        public bool $isActive,
    ) {
    }
}

