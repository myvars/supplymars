<?php

namespace App\Purchasing\Application\Command\Supplier;

use App\Purchasing\Domain\Model\Supplier\SupplierPublicId;

final readonly class DeleteSupplier
{
    public function __construct(public SupplierPublicId $id)
    {
    }
}
