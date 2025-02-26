<?php

namespace App\DTO;

use App\Enum\ShippingMethod;
use App\Validator\ValidCustomerId;
use Symfony\Component\Validator\Constraints as Assert;

class CreateOrderDto
{
    #[Assert\NotBlank(message: 'Please enter a customer Id')]
    #[Assert\Range(notInRangeMessage: 'Please enter a valid customer Id', min: 1, max: 100000)]
    #[ValidCustomerId]
    private ?int $customerId = null;

    #[Assert\NotBlank(message: 'Please enter a shipping method')]
    private ?ShippingMethod $shippingMethod = null;

    private ?string $customerOrderRef = null;

    public function getCustomerId(): ?int
    {
        return $this->customerId;
    }

    public function setCustomerId(?int $customerId): static
    {
        $this->customerId = $customerId;

        return $this;
    }

    public function getCustomerOrderRef(): ?string
    {
        return $this->customerOrderRef;
    }

    public function setCustomerOrderRef(?string $customerOrderRef): static
    {
        $this->customerOrderRef = $customerOrderRef;

        return $this;
    }

    public function getShippingMethod(): ?ShippingMethod
    {
        return $this->shippingMethod;
    }

    public function setShippingMethod(?ShippingMethod $shippingMethod): static
    {
        $this->shippingMethod = $shippingMethod;

        return $this;
    }
}
