<?php

namespace App\Strategy;

use App\DTO\PurchaseOrderItemEditDto;
use App\Entity\PurchaseOrder;
use App\Entity\PurchaseOrderItem;
use App\Service\Crud\Core\CrudUpdateStrategyInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias('app.crud.purchase.order.item.edit.strategy')]
final class CrudPOItemEditStrategy implements CrudUpdateStrategyInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function update(object $entity, ?array $context): void
    {
        assert($entity instanceof PurchaseOrderItemEditDto);
        assert($context['purchaseOrderItem'] instanceof PurchaseOrderItem);

        if ($entity->getQuantity() === $context['purchaseOrderItem']->getQuantity()) {
            return;
        }

        if ($entity->getQuantity() === 0) {
            $this->removePurchaseOrderItem($context['purchaseOrderItem']);
            $this->entityManager->flush();

            return;
        }

        $context['purchaseOrderItem']->updateItem($entity->getQuantity());
        $context['purchaseOrderItem']->getPurchaseOrder()->recalculateTotal();

        $this->entityManager->persist($context['purchaseOrderItem']);
        $this->entityManager->flush();
    }

    private function removePurchaseOrderItem($purchaseOrderItem): void
    {
        $purchaseOrder = $purchaseOrderItem->getPurchaseOrder();
        assert($purchaseOrder instanceof PurchaseOrder);

        $purchaseOrder->removePurchaseOrderItem($purchaseOrderItem);
        $this->entityManager->remove($purchaseOrderItem);

        if ($purchaseOrder->getPurchaseOrderItems()->isEmpty()) {
            $this->removePurchaseOrder($purchaseOrder);
        }
    }

    private function removePurchaseOrder($purchaseOrder): void
    {
        $this->entityManager->remove($purchaseOrder);
    }
}