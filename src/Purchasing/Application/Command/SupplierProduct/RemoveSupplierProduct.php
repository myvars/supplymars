<?php

namespace App\Purchasing\Application\Command\SupplierProduct;

use App\Purchasing\Domain\Model\SupplierProduct\SupplierProductPublicId;

final readonly class RemoveSupplierProduct
{
    public function __construct(public SupplierProductPublicId $id)
    {
    }
}
