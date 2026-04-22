<?php

declare(strict_types=1);

namespace App\Catalog\Application\Command\ProductImage;

use App\Catalog\Domain\Model\Product\ProductPublicId;

final readonly class ReorderProductImage
{
    /**
     * @param array<int, int> $newImageOrder
     */
    public function __construct(
        public ProductPublicId $id,
        public array $newImageOrder = [],
    ) {
    }
}
