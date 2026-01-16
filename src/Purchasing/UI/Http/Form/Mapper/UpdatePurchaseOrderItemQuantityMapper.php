<?php

namespace App\Purchasing\UI\Http\Form\Mapper;

use App\Purchasing\Application\Command\PurchaseOrderItem\UpdatePurchaseOrderItemQuantity;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItemPublicId;
use App\Purchasing\UI\Http\Form\Model\PurchaseOrderItemQuantityForm;

final class UpdatePurchaseOrderItemQuantityMapper
{
    public function __invoke(PurchaseOrderItemQuantityForm $data): UpdatePurchaseOrderItemQuantity
    {
        return new UpdatePurchaseOrderItemQuantity(
            PurchaseOrderItemPublicId::fromString($data->id),
            $data->quantity,
        );
    }
}
