<?php

namespace App\Catalog\Application\Command\ProductImage;

use App\Catalog\Domain\Model\Product\ProductPublicId;

final readonly class ReorderProductImage
{
    public function __construct(
        public ProductPublicId $id,
        public array $newImageOrder = [],
    ) {
    }
}
