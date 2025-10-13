<?php

namespace App\Order\Domain\Model\Order;

use App\Shared\Domain\ValueObject\AbstractIntId;

final readonly class OrderId extends AbstractIntId
{
    // Inherits strict validation and factories.
}
