<?php

declare(strict_types=1);

namespace App\Purchasing\Application\Command\SupplierProduct;

use App\Purchasing\Domain\Model\SupplierProduct\SupplierProductPublicId;

final readonly class MapSupplierProduct
{
    public function __construct(public SupplierProductPublicId $id)
    {
    }
}
