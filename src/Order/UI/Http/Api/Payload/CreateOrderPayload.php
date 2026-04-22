<?php

declare(strict_types=1);

namespace App\Order\UI\Http\Api\Payload;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateOrderPayload
{
    public function __construct(
        #[Assert\NotBlank(message: 'Customer is required.')]
        public string $customer,

        #[Assert\NotBlank(message: 'Shipping method is required.')]
        #[Assert\Choice(choices: ['THREE_DAY', 'NEXT_DAY'], message: 'Invalid shipping method.')]
        public string $shippingMethod,

        public ?string $customerOrderRef = null,
    ) {
    }
}
