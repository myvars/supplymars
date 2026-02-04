<?php

namespace App\Purchasing\Application\Handler\PurchaseOrder;

use App\Purchasing\Application\Command\PurchaseOrder\PurchaseOrderFilter;
use App\Shared\Application\RedirectTarget;
use App\Shared\Application\Result;
use App\Shared\Application\Search\FilterParamBuilder;

final readonly class PurchaseOrderFilterHandler
{
    private const string ROUTE = 'app_purchasing_purchase_order_index';

    public function __construct(private FilterParamBuilder $params)
    {
    }

    public function __invoke(PurchaseOrderFilter $command): Result
    {
        $extras = [
            'purchaseOrderId' => $command->purchaseOrderId,
            'orderId' => $command->orderId,
            'customerId' => $command->customerId,
            'productId' => $command->productId,
            'supplierId' => $command->supplierId,
            'purchaseOrderStatus' => $command->purchaseOrderStatus,
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
