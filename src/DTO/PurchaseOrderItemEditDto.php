<?php

namespace App\DTO;

use App\Entity\PurchaseOrderItem;
use App\Validator\MaxPurchaseOrderItemQty;
use Symfony\Component\Validator\Constraints as Assert;

class PurchaseOrderItemEditDto
{
    #[Assert\NotBlank(message: 'Please enter a purchaseOrderItemId')]
    private int $purchaseOrderItemId;

    #[Assert\NotBlank(message: 'Please enter a product quantity')]
    #[Assert\Range(notInRangeMessage: 'Please enter a product quantity (0 to 100000)', min: 00, max: 100000)]
    #[MaxPurchaseOrderItemQty]
    private ?int $quantity;

    public function __construct(int $purchaseOrderItemId, int $quantity)
    {
        $this->purchaseOrderItemId = $purchaseOrderItemId;
        $this->quantity = $quantity;
    }

    public static function fromEntity(PurchaseOrderItem $purchaseOrderItem): static
    {
        return new static(
            $purchaseOrderItem->getId(),
            $purchaseOrderItem->getQuantity()
        );
    }

    public function getId(): int
    {
        return $this->purchaseOrderItemId;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(?int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }
}
