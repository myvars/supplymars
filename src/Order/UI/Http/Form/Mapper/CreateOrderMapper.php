<?php

declare(strict_types=1);

namespace App\Order\UI\Http\Form\Mapper;

use App\Order\Application\Command\CreateOrder;
use App\Order\UI\Http\Form\Model\OrderForm;

final class CreateOrderMapper
{
    public function __invoke(OrderForm $data): CreateOrder
    {
        return new CreateOrder(
            $data->customerId,
            $data->shippingMethod,
            $data->customerOrderRef
        );
    }
}
