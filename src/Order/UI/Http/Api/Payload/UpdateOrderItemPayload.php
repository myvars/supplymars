<?php

namespace App\Order\UI\Http\Api\Payload;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateOrderItemPayload
{
    public function __construct(
        #[Assert\NotBlank(message: 'Quantity is required.')]
        #[Assert\Positive(message: 'Quantity must be positive.')]
        public int $quantity,
    ) {
    }
}
