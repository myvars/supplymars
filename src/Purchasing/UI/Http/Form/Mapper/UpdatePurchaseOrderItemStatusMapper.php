<?php

namespace App\Purchasing\UI\Http\Form\Mapper;

use App\Purchasing\Application\Command\PurchaseOrderItem\UpdatePurchaseOrderItemStatus;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItemPublicId;
use App\Purchasing\UI\Http\Form\Model\PurchaseOrderItemStatusForm;

final class UpdatePurchaseOrderItemStatusMapper
{
    public function __invoke(PurchaseOrderItemStatusForm $data): UpdatePurchaseOrderItemStatus
    {
        return new UpdatePurchaseOrderItemStatus(
            PurchaseOrderItemPublicId::fromString($data->id),
            $data->purchaseOrderItemStatus,
        );
    }
}
