<?php

namespace App\Purchasing\Domain\Repository;

use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItem;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItemId;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItemPublicId;
use App\Purchasing\Infrastructure\Persistence\Doctrine\PurchaseOrderItemDoctrineRepository;
use App\Shared\Infrastructure\Persistence\Search\FindByCriteriaInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PurchaseOrderItemDoctrineRepository::class)]
interface PurchaseOrderItemRepository
{
    public function add(PurchaseOrderItem $purchaseOrderItem): void;
    public function remove(PurchaseOrderItem $purchaseOrderItem): void;
    public function get(PurchaseOrderItemId $id): ?PurchaseOrderItem;
    public function getByPublicId(PurchaseOrderItemPublicId $publicId): ?PurchaseOrderItem;
}
