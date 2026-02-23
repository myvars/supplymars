<?php

namespace App\Order\UI\Http\Api\Payload;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class AddOrderItemPayload
{
    public function __construct(
        #[Assert\NotBlank(message: 'Product is required.')]
        public string $product,

        #[Assert\NotBlank(message: 'Quantity is required.')]
        #[Assert\Positive(message: 'Quantity must be positive.')]
        public int $quantity,
    ) {
    }
}
