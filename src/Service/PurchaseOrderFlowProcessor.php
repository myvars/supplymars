<?php

namespace App\Service;

use App\Entity\PurchaseOrder;
use App\Entity\PurchaseOrderStatus;
use Doctrine\ORM\EntityManagerInterface;

class PurchaseOrderFlowProcessor
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function updateStatus(PurchaseOrder $purchaseOrder, PurchaseOrderStatus $newStatus, bool $flush=true): void
    {
        $currentStatus = $purchaseOrder->getStatus();
        if ($newStatus === $currentStatus) {
            return;
        }

        if (!$currentStatus->canTransitionTo($newStatus)) {
            throw new \LogicException(sprintf(
                'Cannot transition from "%s" to "%s"',
                $currentStatus->value,
                $newStatus->value
            ));
        }

        $this->checkPurchaseOrderItemsAllowTransition($purchaseOrder, $newStatus);

        $purchaseOrder->setStatus($newStatus);
        $this->entityManager->persist($purchaseOrder);

        if ($flush) {
            $this->entityManager->flush();
        }
    }

    private function checkPurchaseOrderItemsAllowTransition(
        PurchaseOrder $purchaseOrder,
        PurchaseOrderStatus $newStatus
    ): void {
        if ($purchaseOrder->getPurchaseOrderItems()->isEmpty()) {
            throw new \LogicException(sprintf(
                'Cannot transition to "%s", purchase order has no items',
                $newStatus->value
            ));
        }

        foreach ($purchaseOrder->getPurchaseOrderItems() as $purchaseOrderItem) {
            $itemStatus = $purchaseOrderItem->getStatus();
            if ($itemStatus !== $newStatus) {
                throw new \LogicException(sprintf(
                    'Cannot transition to "%s", purchase order has "%s" item',
                    $newStatus->value,
                    $itemStatus->value
                ));
            }
        }
    }
}