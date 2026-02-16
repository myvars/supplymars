<?php

namespace App\Purchasing\Domain\Repository;

use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrder;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderId;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderPublicId;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;
use App\Purchasing\Domain\Model\Supplier\Supplier;
use App\Purchasing\Infrastructure\Persistence\Doctrine\PurchaseOrderDoctrineRepository;
use App\Shared\Infrastructure\Persistence\Search\FindByCriteriaInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PurchaseOrderDoctrineRepository::class)]
interface PurchaseOrderRepository extends FindByCriteriaInterface
{
    public function add(PurchaseOrder $purchaseOrder): void;

    public function remove(PurchaseOrder $purchaseOrder): void;

    public function get(PurchaseOrderId $id): ?PurchaseOrder;

    public function getByPublicId(PurchaseOrderPublicId $publicId): ?PurchaseOrder;

    /** @return PurchaseOrder[] */
    public function findWaitingPurchaseOrders(Supplier $supplier, int $limit = 10): array;

    /** @return PurchaseOrder[] */
    public function findByStatus(PurchaseOrderStatus $status, int $limit): array;

    /** @return PurchaseOrder[] */
    public function findWithMixedItemStatusesIncludingRejected(int $daysBack = 30, int $limit = 100): array;

    public function countRejectedPurchaseOrders(): int;
}
