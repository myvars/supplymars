<?php

declare(strict_types=1);

namespace App\Order\Application\Command;

use App\Order\Domain\Model\Order\ShippingMethod;

final readonly class CreateOrder
{
    public function __construct(
        public int $customerId,
        public ShippingMethod $shippingMethod,
        public ?string $customerOrderRef,
    ) {
    }
}
