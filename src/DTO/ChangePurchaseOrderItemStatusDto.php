<?php

namespace App\DTO;

use App\Entity\PurchaseOrderItem;
use App\Enum\PurchaseOrderStatus;
use App\Validator\ValidPOItemStatusChange;
use Symfony\Component\Validator\Constraints as Assert;

class ChangePurchaseOrderItemStatusDto
{
    public function __construct(
        #[Assert\Positive(message: 'Please enter a valid purchaseOrderItemId')]
        private readonly int $purchaseOrderItemId,
        #[Assert\NotBlank(message: 'Please enter a PO Item status')]
        #[ValidPOItemStatusChange]
        private ?PurchaseOrderStatus $purchaseOrderItemStatus,
    ) {
    }

    public static function fromEntity(PurchaseOrderItem $purchaseOrderItem): static
    {
        return new static($purchaseOrderItem->getId(), $purchaseOrderItem->getStatus());
    }

    public function getId(): int
    {
        return $this->purchaseOrderItemId;
    }

    public function getPurchaseOrderItemStatus(): ?PurchaseOrderStatus
    {
        return $this->purchaseOrderItemStatus;
    }

    public function setPurchaseOrderItemStatus(?PurchaseOrderStatus $purchaseOrderItemStatus): static
    {
        $this->purchaseOrderItemStatus = $purchaseOrderItemStatus;

        return $this;
    }
}
