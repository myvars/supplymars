<?php

namespace App\Service\PurchaseOrder;

use App\DTO\PurchaseOrderItemEditDto;
use App\Entity\CustomerOrderItem;
use App\Entity\PurchaseOrder;
use App\Entity\PurchaseOrderItem;
use Doctrine\ORM\EntityManagerInterface;

final class PurchaseOrderItemUpdater
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function update(PurchaseOrderItemEditDto $dto, bool $flush = true): void
    {
        $purchaseOrderItem =$this->getPurchaseOrderItem($dto->getId());

        if ($dto->getQuantity() === $purchaseOrderItem->getQuantity()) {
            return;
        }

        if ($dto->getQuantity() === 0) {
            $this->removePurchaseOrderItem($purchaseOrderItem);
        } else {
            $purchaseOrderItem->updateItem($dto->getQuantity());
            $purchaseOrderItem->getPurchaseOrder()->recalculateTotal();
            $this->entityManager->persist($purchaseOrderItem);
        }

        if ($flush) {
            $this->entityManager->flush();
        }
    }

    private function removePurchaseOrderItem(PurchaseOrderItem $purchaseOrderItem): void
    {
        $customerOrderItem = $this->getCustomerOrderItem($purchaseOrderItem);
        $purchaseOrder = $this->getPurchaseOrder($purchaseOrderItem);

        $customerOrderItem->removePurchaseOrderItem($purchaseOrderItem);
        $this->entityManager->persist($customerOrderItem);

        $purchaseOrder->removePurchaseOrderItem($purchaseOrderItem);
        $this->entityManager->remove($purchaseOrderItem);
        if ($purchaseOrder->getPurchaseOrderItems()->isEmpty()) {
            $this->entityManager->remove($purchaseOrder);
        }
    }

    public function getPurchaseOrderItem(int $id): PurchaseOrderItem
    {
        return $this->entityManager->getRepository(PurchaseOrderItem::class)->find($id);
    }

    public function getPurchaseOrder(PurchaseOrderItem $purchaseOrderItem): PurchaseOrder
    {
        return $purchaseOrderItem->getPurchaseOrder();
    }

    public function getCustomerOrderItem(PurchaseOrderItem $purchaseOrderItem): CustomerOrderItem
    {
        return $purchaseOrderItem->getCustomerOrderItem();
    }
}