<?php

namespace App\Purchasing\Application\Command\PurchaseOrderItem;

use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItemPublicId;

final readonly class UpdatePurchaseOrderItemQuantity
{
    public function __construct(
        public PurchaseOrderItemPublicId $id,
        public int $quantity,
    ) {
    }
}
