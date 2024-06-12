<?php

namespace App\Service\PurchaseOrder;

use App\DTO\PurchaseOrderItemStatusChangeDto;
use App\Entity\PurchaseOrder;
use App\Entity\PurchaseOrderItem;
use Doctrine\ORM\EntityManagerInterface;

final class PurchaseOrderItemStatusUpdater
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function update(PurchaseOrderItemStatusChangeDto $dto, bool $flush = true): void
    {
        $purchaseOrderItem =$this->getPurchaseOrderItem($dto->getId());
        $purchaseOrder = $this->getPurchaseOrder($purchaseOrderItem);

        if ($dto->getPurchaseOrderItemStatus() === $purchaseOrderItem->getStatus()) {
            return;
        }

        $purchaseOrderItem->updateStatus($dto->getPurchaseOrderItemStatus());
        $this->entityManager->persist($purchaseOrderItem);

        if ($flush) {
            $this->entityManager->flush();
        }
    }

    private function getPurchaseOrderItem(int $id): PurchaseOrderItem
    {
        return $this->entityManager->getRepository(PurchaseOrderItem::class)->find($id);
    }

    private function getPurchaseOrder(PurchaseOrderItem $purchaseOrderItem): PurchaseOrder
    {
        return $purchaseOrderItem->getPurchaseOrder();
    }
}