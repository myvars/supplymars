<?php

namespace App\Service;

use App\Entity\CustomerOrder;
use App\Entity\CustomerOrderItem;
use App\Entity\PurchaseOrder;
use App\Entity\PurchaseOrderItem;
use App\Entity\StatusChangeLog;
use App\Enum\DomainEventType;
use Doctrine\ORM\EntityManagerInterface;

final readonly class StatusLogUtility
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function forCustomerOrder(CustomerOrder $customerOrder): ?array
    {
        return $this->getStatusLogs(
            DomainEventType::ORDER_STATUS_CHANGED,
            $customerOrder->getId()
        );
    }

    public function forCustomerOrderItem(CustomerOrderItem $customerOrderItem): ?array
    {
        return $this->getStatusLogs(
            DomainEventType::ORDER_ITEM_STATUS_CHANGED,
            $customerOrderItem->getId()
        );
    }

    public function forPurchaseOrder(PurchaseOrder $purchaseOrder): ?array
    {
        return $this->getStatusLogs(
            DomainEventType::PURCHASE_ORDER_STATUS_CHANGED,
            $purchaseOrder->getId()
        );
    }

    public function forPurchaseOrderItem(PurchaseOrderItem $purchaseOrderItem): ?array
    {
        return $this->getStatusLogs(
            DomainEventType::PURCHASE_ORDER_ITEM_STATUS_CHANGED,
            $purchaseOrderItem->getId()
        );
    }

    private function getStatusLogs(DomainEventType $eventType, int $eventTypeId): array
    {
        return $this->entityManager->getRepository(StatusChangeLog::class)
            ->findBy([
                'eventType' => $eventType,
                'eventTypeId' => $eventTypeId
            ], ['createdAt' => 'ASC']);
    }
}