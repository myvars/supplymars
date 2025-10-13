<?php

namespace App\Purchasing\UI\Http\Form\Model;

use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItem;
use App\Purchasing\UI\Http\Validation\MaxPurchaseOrderItemQty;
use Symfony\Component\Validator\Constraints as Assert;

final class PurchaseOrderItemQuantityForm
{
    public ?string $id = null;

    #[Assert\NotBlank(message: 'Please enter a product quantity')]
    #[Assert\Range(notInRangeMessage: 'Quantity must be between {{ min }} and {{ max }}', min: 0, max: 10000)]
    #[MaxPurchaseOrderItemQty]
    public ?int $quantity = null;

    public static function fromEntity(PurchaseOrderItem $purchaseOrderItem): self
    {
        $form = new self();

        $form->id = $purchaseOrderItem->getPublicId()->value();
        $form->quantity = $purchaseOrderItem->getQuantity();

        return $form;
    }
}
