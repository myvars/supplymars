<?php

namespace App\Purchasing\Domain\Repository;

use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrder;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderId;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderPublicId;
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
}
