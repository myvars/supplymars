<?php

namespace App\Purchasing\UI\Http\Form\Model;

use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItem;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;
use App\Purchasing\UI\Http\Validation\ValidPOItemStatusChange;
use Symfony\Component\Validator\Constraints as Assert;

final class PurchaseOrderItemStatusForm
{
    public ?string $id = null;

    #[Assert\NotBlank(message: 'Please enter a PO Item status')]
    #[ValidPOItemStatusChange]
    public ?PurchaseOrderStatus $purchaseOrderItemStatus = null;

    public static function fromEntity(PurchaseOrderItem $purchaseOrderItem): self
    {
        $form = new self();

        $form->id = $purchaseOrderItem->getPublicId()->value();
        $form->purchaseOrderItemStatus = $purchaseOrderItem->getStatus();

        return $form;
    }
}
