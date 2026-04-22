<?php

declare(strict_types=1);

namespace App\Purchasing\Application\Command\PurchaseOrderItem;

use App\Order\Domain\Model\Order\OrderItemPublicId;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProductPublicId;

final readonly class CreatePurchaseOrderItem
{
    public function __construct(
        public OrderItemPublicId $id,
        public SupplierProductPublicId $supplierProductId,
    ) {
    }
}
