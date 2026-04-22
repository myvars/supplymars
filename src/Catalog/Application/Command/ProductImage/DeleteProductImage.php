<?php

declare(strict_types=1);

namespace App\Catalog\Application\Command\ProductImage;

use App\Catalog\Domain\Model\ProductImage\ProductImagePublicId;

final readonly class DeleteProductImage
{
    public function __construct(public ProductImagePublicId $id)
    {
    }
}
