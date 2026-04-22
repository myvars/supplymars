<?php

declare(strict_types=1);

namespace App\Order\Application\Service;

use App\Customer\Domain\Model\User\User;
use App\Order\Domain\Model\Order\CustomerOrder;

final readonly class DemoOrderResult
{
    public function __construct(
        public CustomerOrder $order,
        public User $customer,
    ) {
    }
}
