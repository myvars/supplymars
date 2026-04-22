<?php

declare(strict_types=1);

namespace App\Purchasing\UI\Http\Form\Mapper;

use App\Purchasing\Application\Command\PurchaseOrder\PurchaseOrderFilter;
use App\Purchasing\Application\Search\PurchaseOrderSearchCriteria;

final class PurchaseOrderFilterMapper
{
    public function __invoke(PurchaseOrderSearchCriteria $criteria): PurchaseOrderFilter
    {
        return new PurchaseOrderFilter(
            $criteria->getQuery(),
            $criteria->getSort(),
            $criteria->getSortDirection(),
            $criteria->getPage(),
            $criteria->getLimit(),
            $criteria->purchaseOrderId,
            $criteria->orderId,
            $criteria->customerId,
            $criteria->productId,
            $criteria->supplierId,
            $criteria->purchaseOrderStatus,
            $criteria->startDate,
            $criteria->endDate,
        );
    }
}
