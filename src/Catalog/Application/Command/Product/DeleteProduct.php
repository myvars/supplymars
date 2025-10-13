<?php

namespace App\Catalog\Application\Command\Product;

use App\Catalog\Domain\Model\Product\ProductPublicId;

final readonly class DeleteProduct
{
    public function __construct(public ProductPublicId $id)
    {
    }
}
