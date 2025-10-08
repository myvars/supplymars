<?php

namespace App\Service\OrderProcessing;

use App\Entity\CustomerOrder;
use Doctrine\ORM\EntityManagerInterface;

final readonly class RefreshOrderStatus
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function refresh(CustomerOrder $customerOrder): array
    {
        $refreshLog = [];
        $previousStatus = $customerOrder->getStatus();

        $this->refreshPurchaseOrders($customerOrder, $refreshLog);
        $this->refreshCustomerOrderItems($customerOrder, $refreshLog);

        if ($previousStatus !== $customerOrder->getStatus()) {
            $refreshLog[] = $this->formatLogMessage(
                'Customer Order',
                $customerOrder->getId(),
                $previousStatus->value,
                $customerOrder->getStatus()->value
            );
        }

        $this->entityManager->flush();

        return $refreshLog;
    }

    private function refreshPurchaseOrders(CustomerOrder $customerOrder, array &$refreshLog): void
    {
        foreach ($customerOrder->getPurchaseOrders() as $purchaseOrder) {
            $previousStatus = $purchaseOrder->getStatus();
            $purchaseOrder->generateStatus();

            if ($previousStatus !== $purchaseOrder->getStatus()) {
                $refreshLog[] = $this->formatLogMessage(
                    'Purchase Order',
                    $purchaseOrder->getId(),
                    $previousStatus->value,
                    $purchaseOrder->getStatus()->value
                );
            }
        }
    }

    private function refreshCustomerOrderItems(CustomerOrder $customerOrder, array &$refreshLog): void
    {
        foreach ($customerOrder->getCustomerOrderItems() as $customerOrderItem) {
            $previousStatus = $customerOrderItem->getStatus();
            $customerOrderItem->generateStatus();

            if ($previousStatus !== $customerOrderItem->getStatus()) {
                $refreshLog[] = $this->formatLogMessage(
                    'Customer Order Item',
                    $customerOrderItem->getId(),
                    $previousStatus->value,
                    $customerOrderItem->getStatus()->value
                );
            }
        }
    }

    private function formatLogMessage(string $entityType, int $id, string $fromStatus, string $toStatus): string
    {
        return sprintf('%s %s status changed from %s to %s', $entityType, $id, $fromStatus, $toStatus);
    }
}
