<?php

namespace App\Order\Application\Command;

use App\Order\Domain\Model\Order\OrderItemPublicId;

final readonly class CancelOrderItem
{
    public function __construct(public OrderItemPublicId $id)
    {
    }
}
