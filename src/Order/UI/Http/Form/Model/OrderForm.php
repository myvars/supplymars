<?php

namespace App\Order\UI\Http\Form\Model;

use App\Order\UI\Http\Validation\ValidCustomerId;
use App\Shared\Domain\ValueObject\ShippingMethod;
use Symfony\Component\Validator\Constraints as Assert;

final class OrderForm
{
    #[Assert\NotBlank(message: 'Please enter a customer Id')]
    #[Assert\Range(notInRangeMessage: 'Please enter a valid customer Id', min: 1, max: 100000)]
    #[ValidCustomerId]
    public ?int $customerId = null;

    #[Assert\NotNull(message: 'Please choose a shipping method')]
    public ?ShippingMethod $shippingMethod = null;

    public ?string $customerOrderRef = null;
}
