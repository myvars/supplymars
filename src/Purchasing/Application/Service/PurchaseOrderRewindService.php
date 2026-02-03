<?php

namespace App\Purchasing\Application\Service;

use App\Audit\Domain\Repository\StatusChangeLogRepository;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrder;
use App\Shared\Application\FlusherInterface;
use App\Shared\Domain\Event\DomainEventType;

final readonly class PurchaseOrderRewindService
{
    public function __construct(
        private StatusChangeLogRepository $statusChangeLogs,
        private FlusherInterface $flusher,
    ) {
    }

    public function rewind(PurchaseOrder $purchaseOrder): void
    {
        $this->removeStatusLogs(
            DomainEventType::PURCHASE_ORDER_STATUS_CHANGED,
            $purchaseOrder->getId()
        );

        foreach ($purchaseOrder->getPurchaseOrderItems() as $item) {
            $this->removeStatusLogs(
                DomainEventType::PURCHASE_ORDER_ITEM_STATUS_CHANGED,
                $item->getId()
            );
        }

        $purchaseOrder->forceRewindToPending();

        $purchaseOrder->getCustomerOrder()->generateStatus();

        $this->flusher->flush();
    }

    private function removeStatusLogs(DomainEventType $eventType, int $entityId): void
    {
        $logs = $this->statusChangeLogs->findByEvent($eventType, $entityId);
        foreach ($logs as $log) {
            $this->statusChangeLogs->remove($log);
        }
    }
}
