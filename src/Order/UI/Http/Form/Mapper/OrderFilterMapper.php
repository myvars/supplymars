<?php

namespace App\Order\UI\Http\Form\Mapper;

use App\Order\Application\Command\OrderFilter;
use App\Order\Application\Search\OrderSearchCriteria;

final class OrderFilterMapper
{
    public function __invoke(OrderSearchCriteria $criteria): OrderFilter
    {
        return new OrderFilter(
            $criteria->getQuery(),
            $criteria->getSort(),
            $criteria->getSortDirection(),
            $criteria->getPage(),
            $criteria->getLimit(),
            $criteria->orderId,
            $criteria->purchaseOrderId,
            $criteria->customerId,
            $criteria->productId,
            $criteria->orderStatus,
            $criteria->startDate,
            $criteria->endDate,
        );
    }
}
