<?php

namespace App\Catalog\Application\Command\Subcategory;

use App\Catalog\Domain\Model\Category\CategoryId;
use App\Shared\Domain\ValueObject\PriceModel;

final readonly class CreateSubcategory
{
    /**
     * @param numeric-string $defaultMarkup
     */
    public function __construct(
        public string $name,
        public CategoryId $categoryId,
        public string $defaultMarkup,
        public PriceModel $priceModel,
        public ?int $ownerId,
        public bool $isActive,
    ) {
    }
}
