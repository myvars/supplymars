<?php

namespace App\DTO;

use App\Entity\CustomerOrderItem;
use App\Validator\MinOrderItemQty;
use Symfony\Component\Validator\Constraints as Assert;

class EditOrderItemDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'Please enter a orderItemId')]
        private readonly int $orderItemId,
        #[Assert\NotBlank(message: 'Please enter a product quantity')]
        #[Assert\Range(notInRangeMessage: 'Please enter a product quantity (0 to 100000)', min: 0, max: 100000)]
        #[MinOrderItemQty]
        private ?int $quantity,
        #[Assert\NotBlank(message: 'Please enter a product price including VAT')]
        #[Assert\Range(notInRangeMessage: 'Please enter a product price inc VAT (0 to 100000)', min: 0, max: 100000)]
        private ?string $priceIncVat
    ) {
    }

    public static function fromEntity(CustomerOrderItem $customerOrderItem): static
    {
        return new static(
            $customerOrderItem->getId(),
            $customerOrderItem->getQuantity(),
            $customerOrderItem->getPriceIncVat()
        );
    }

    public function getId(): int
    {
        return $this->orderItemId;
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

    public function getPriceIncVat(): ?string
    {
        return $this->priceIncVat;
    }

    public function setPriceIncVat(?string $priceIncVat): static
    {
        $this->priceIncVat = $priceIncVat;

        return $this;
    }
}
