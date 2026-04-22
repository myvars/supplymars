<?php

declare(strict_types=1);

namespace App\Purchasing\Domain\Repository;

use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItem;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItemId;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItemPublicId;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;
use App\Purchasing\Domain\Model\Supplier\Supplier;
use App\Purchasing\Infrastructure\Persistence\Doctrine\PurchaseOrderItemDoctrineRepository;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PurchaseOrderItemDoctrineRepository::class)]
interface PurchaseOrderItemRepository
{
    public function add(PurchaseOrderItem $purchaseOrderItem): void;

    public function remove(PurchaseOrderItem $purchaseOrderItem): void;

    public function get(PurchaseOrderItemId $id): ?PurchaseOrderItem;

    public function getByPublicId(PurchaseOrderItemPublicId $publicId): ?PurchaseOrderItem;

    /** @return PurchaseOrderItem[] */
    public function findPurchaseOrderItemsByStatus(
        Supplier $supplier,
        PurchaseOrderStatus $status,
        int $limit = 10,
    ): array;
}
