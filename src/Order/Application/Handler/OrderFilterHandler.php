<?php

declare(strict_types=1);

namespace App\Order\Application\Handler;

use App\Order\Application\Command\OrderFilter;
use App\Shared\Application\RedirectTarget;
use App\Shared\Application\Result;
use App\Shared\Application\Search\FilterParamBuilder;

final readonly class OrderFilterHandler
{
    private const string ROUTE = 'app_order_index';

    public function __construct(private FilterParamBuilder $params)
    {
    }

    public function __invoke(OrderFilter $command): Result
    {
        $extras = [
            'orderId' => $command->orderId,
            'purchaseOrderId' => $command->purchaseOrderId,
            'customerId' => $command->customerId,
            'productId' => $command->productId,
            'orderStatus' => $command->orderStatus,
            'startDate' => $command->startDate,
            'endDate' => $command->endDate,
        ];

        return Result::ok(
            redirect: new RedirectTarget(
                route: self::ROUTE,
                params: $this->params->build($command, $extras),
            )
        );
    }
}
