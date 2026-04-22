<?php

declare(strict_types=1);

namespace App\Purchasing\Application\Command\PurchaseOrder;

use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderPublicId;

final readonly class RewindPurchaseOrder
{
    public function __construct(public PurchaseOrderPublicId $id)
    {
    }
}
