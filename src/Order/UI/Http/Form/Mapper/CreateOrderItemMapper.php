<?php

namespace App\Order\UI\Http\Form\Mapper;

use App\Order\Application\Command\CreateOrderItem;
use App\Order\Domain\Model\Order\OrderPublicId;
use App\Order\UI\Http\Form\Model\OrderItemForm;

final class CreateOrderItemMapper
{
    public function __invoke(OrderItemForm $data): CreateOrderItem
    {
        return new CreateOrderItem(
            OrderPublicId::fromString($data->orderId),
            $data->productId,
            $data->quantity,
        );
    }
}
