<?php

declare(strict_types=1);

namespace App\Purchasing\Application\Command\PurchaseOrderItem;

use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItemPublicId;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;

final readonly class UpdatePurchaseOrderItemStatus
{
    public function __construct(
        public PurchaseOrderItemPublicId $id,
        public PurchaseOrderStatus $purchaseOrderStatus,
    ) {
    }
}
