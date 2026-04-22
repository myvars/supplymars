<?php

declare(strict_types=1);

namespace App\Purchasing\Application\Handler\PurchaseOrder;

use App\Purchasing\Application\Command\PurchaseOrder\RewindPurchaseOrder;
use App\Purchasing\Application\Service\PurchaseOrderRewindService;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrder;
use App\Purchasing\Domain\Repository\PurchaseOrderRepository;
use App\Shared\Application\Result;

final readonly class RewindPurchaseOrderHandler
{
    public function __construct(
        private PurchaseOrderRepository $purchaseOrders,
        private PurchaseOrderRewindService $rewindService,
    ) {
    }

    public function __invoke(RewindPurchaseOrder $command): Result
    {
        $purchaseOrder = $this->purchaseOrders->getByPublicId($command->id);
        if (!$purchaseOrder instanceof PurchaseOrder) {
            return Result::fail('Purchase order not found.');
        }

        $this->rewindService->rewind($purchaseOrder);

        return Result::ok('Purchase order rewound to pending.');
    }
}
