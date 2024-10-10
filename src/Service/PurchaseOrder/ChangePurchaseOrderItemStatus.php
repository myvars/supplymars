<?php

namespace App\Service\PurchaseOrder;

use App\DTO\ChangePurchaseOrderItemStatusDto;
use App\Entity\PurchaseOrderItem;
use App\Service\Crud\Core\CrudActionInterface;
use App\Service\DomainEventDispatcher;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ChangePurchaseOrderItemStatus implements CrudActionInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private DomainEventDispatcher $domainEventDispatcher
    ) {
    }

    public function handle(object $crudOptions): void
    {
        $entity = $crudOptions->getEntity();
        assert($entity instanceof ChangePurchaseOrderItemStatusDto);

        $this->fromDto($entity);
    }

    public function fromDto(ChangePurchaseOrderItemStatusDto $dto): void
    {
        $purchaseOrderItem = $this->getPurchaseOrderItem($dto->getId());

        if (!$purchaseOrderItem->allowStatusChange()) {
            throw new \DomainException('Purchase order item cannot be edited');
        }

        if ($dto->getPurchaseOrderItemStatus() === $purchaseOrderItem->getStatus()) {
            return;
        }

        $purchaseOrderItem->updateStatus($dto->getPurchaseOrderItemStatus());

        $this->entityManager->persist($purchaseOrderItem);
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
}