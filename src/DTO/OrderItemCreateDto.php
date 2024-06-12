<?php

namespace App\DTO;

use App\Entity\CustomerOrder;
use App\Validator\ValidProductId;
use Symfony\Component\Validator\Constraints as Assert;

class OrderItemCreateDto
{
    #[Assert\NotBlank(message: 'Please enter a orderId')]
    private int $orderId;

    #[Assert\NotBlank(message: 'Please enter a product Id')]
    #[Assert\Range(notInRangeMessage: 'Please enter a valid product Id', min: 1, max: 100000)]
    #[ValidProductId]
    private ?int $productId = null;

    #[Assert\NotBlank(message: 'Please enter a product quantity')]
    #[Assert\Range(notInRangeMessage: 'Please enter a product quantity (1 to 100000)', min: 1, max: 100000)]
    private ?int $quantity = 1;

    public function __construct(int $orderId, ?int $productId, int $quantity)
    {
        $this->orderId = $orderId;
        $this->productId = $productId;
        $this->quantity = $quantity;
    }

    public static function fromEntity(CustomerOrder $customerOrder): static
    {
        return new static($customerOrder->getId(), null, 1);
    }

    public function getId(): int
    {
        return $this->orderId;
    }

    public function setOrderId(int $orderId): static
    {
        $this->orderId = $orderId;

        return $this;
    }

    public function getProductId(): ?int
    {
        return $this->productId;
    }

    public function setProductId(?int $productId): static
    {
        $this->productId = $productId;

        return $this;
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
