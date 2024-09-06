<?php

namespace App\Service\PurchaseOrder;

use App\DTO\EditPurchaseOrderItemDto;
use App\Entity\CustomerOrderItem;
use App\Entity\PurchaseOrder;
use App\Entity\PurchaseOrderItem;
use App\Service\Crud\Core\CrudActionInterface;
use App\Service\DomainEventDispatcher;
use Doctrine\ORM\EntityManagerInterface;

final readonly class EditPurchaseOrderItem implements CrudActionInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private DomainEventDispatcher $domainEventDispatcher
    ) {
    }

    public function handle(object $entity, ?array $context): void
    {
        assert($entity instanceof EditPurchaseOrderItemDto);

        $this->fromDto($entity);
    }

    public function fromDto(EditPurchaseOrderItemDto $dto): void
    {
        $purchaseOrderItem = $this->getPurchaseOrderItem($dto->getId());

        if (!$purchaseOrderItem->allowEdit()) {
            throw new \DomainException('Purchase order item cannot be edited');
        }

        if ($dto->getQuantity() === $purchaseOrderItem->getQuantity()) {
            return;
        }

        $this->editPurchaseOrderItem($purchaseOrderItem, $dto->getQuantity());

        $this->entityManager->flush();

        $this->domainEventDispatcher->dispatchProviderEvents([
            $purchaseOrderItem,
            $purchaseOrderItem->getPurchaseOrder(),
            $purchaseOrderItem->getCustomerOrderItem(),
            $purchaseOrderItem->getCustomerOrderItem()->getCustomerOrder()
        ]);
    }

    private function getPurchaseOrderItem(int $id): PurchaseOrderItem
    {
        return $this->entityManager->getRepository(PurchaseOrderItem::class)->find($id);
    }

    private function editPurchaseOrderItem(PurchaseOrderItem $purchaseOrderItem, int $qty): void
    {
        if ($qty === 0) {
            $this->removePurchaseOrderItem($purchaseOrderItem);

            return;
        }

        $this->updatePurchaseOrderItem($purchaseOrderItem, $qty);
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
            $this->removePurchaseOrder($purchaseOrder);
        }
    }

    private function updatePurchaseOrderItem(PurchaseOrderItem $purchaseOrderItem, int $qty): void
    {
        $purchaseOrderItem->updateItem($qty);
        $purchaseOrderItem->getPurchaseOrder()->recalculateTotal();

        $this->entityManager->persist($purchaseOrderItem);
    }

    private function getPurchaseOrder(PurchaseOrderItem $purchaseOrderItem): PurchaseOrder
    {
        return $purchaseOrderItem->getPurchaseOrder();
    }

    private function getCustomerOrderItem(PurchaseOrderItem $purchaseOrderItem): CustomerOrderItem
    {
        return $purchaseOrderItem->getCustomerOrderItem();
    }

    private function removePurchaseOrder(PurchaseOrder $purchaseOrder): void
    {
        $this->entityManager->remove($purchaseOrder);
    }
}