<?php

namespace App\Order\Application\Command;

use App\Shared\Application\Search\FilterCommand;

final readonly class OrderFilter extends FilterCommand
{
    public function __construct(
        ?string $query,
        string $sort,
        string $sortDirection,
        int $page,
        int $limit,
        public ?int $orderId,
        public ?int $purchaseOrderId,
        public ?int $customerId,
        public ?int $productId,
        public ?string $orderStatus,
        public ?string $startDate,
        public ?string $endDate,
    ) {
        parent::__construct($query, $sort, $sortDirection, $page, $limit);
    }
}
