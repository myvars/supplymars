<?php

namespace App\Purchasing\Application\Command\PurchaseOrder;

use App\Shared\Application\Search\FilterCommand;

final readonly class PurchaseOrderFilter extends FilterCommand
{
    public function __construct(
        ?string $query,
        string $sort,
        string $sortDirection,
        int $page,
        int $limit,
        public ?int $purchaseOrderId,
        public ?int $orderId,
        public ?int $customerId,
        public ?int $productId,
        public ?int $supplierId,
        public ?string $purchaseOrderStatus,
        public ?string $startDate,
        public ?string $endDate,
    ) {
        parent::__construct($query, $sort, $sortDirection, $page, $limit);
    }
}
