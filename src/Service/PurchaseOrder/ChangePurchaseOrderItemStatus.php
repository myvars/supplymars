<?php

namespace App\Service\PurchaseOrder;

use App\DTO\ChangePurchaseOrderItemStatusDto;
use App\Entity\PurchaseOrderItem;
use App\Service\Crud\Core\CrudActionInterface;
use Doctrine\ORM\EntityManagerInterface;

final class ChangePurchaseOrderItemStatus implements CrudActionInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function handle(object $entity, ?array $context): void
    {
        assert($entity instanceof ChangePurchaseOrderItemStatusDto);
        $this->fromDto($entity);
    }

    public function fromDto(ChangePurchaseOrderItemStatusDto $dto, bool $flush = true): void
    {
        $purchaseOrderItem =$this->getPurchaseOrderItem($dto->getId());

        if (!$purchaseOrderItem->allowEdit()) {
            throw new \DomainException('Purchase order item cannot be edited');
        }

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
}