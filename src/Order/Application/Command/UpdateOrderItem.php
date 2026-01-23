<?php

namespace App\Order\Application\Command;

use App\Order\Domain\Model\Order\OrderItemPublicId;

final readonly class UpdateOrderItem
{
    /**
     * @param numeric-string $priceIncVat
     */
    public function __construct(
        public OrderItemPublicId $orderItemId,
        public int $quantity,
        public string $priceIncVat,
    ) {
    }
}
