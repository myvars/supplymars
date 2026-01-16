<?php

namespace App\Order\UI\Http\Form\Mapper;

use App\Order\Application\Command\UpdateOrderItem;
use App\Order\Domain\Model\Order\OrderItemPublicId;
use App\Order\UI\Http\Form\Model\UpdateOrderItemForm;

final class UpdateOrderItemMapper
{
    public function __invoke(UpdateOrderItemForm $data): UpdateOrderItem
    {
        return new UpdateOrderItem(
            OrderItemPublicId::fromString($data->orderItemId),
            $data->quantity,
            $data->priceIncVat,
        );
    }
}
