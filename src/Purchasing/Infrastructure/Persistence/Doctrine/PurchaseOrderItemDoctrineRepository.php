<?php

namespace App\Purchasing\Infrastructure\Persistence\Doctrine;

use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItem;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItemId;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItemPublicId;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;
use App\Purchasing\Domain\Model\Supplier\Supplier;
use App\Purchasing\Domain\Repository\PurchaseOrderItemRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PurchaseOrderItem>
 *
 * @method PurchaseOrderItem|null find($id, $lockMode = null, $lockVersion = null)
 * @method PurchaseOrderItem|null findOneBy(array $criteria, array $orderBy = null)
 * @method PurchaseOrderItem[]    findAll()
 * @method PurchaseOrderItem[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PurchaseOrderItemDoctrineRepository extends ServiceEntityRepository implements PurchaseOrderItemRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PurchaseOrderItem::class);
    }

    public function add(PurchaseOrderItem $purchaseOrderItem): void
    {
        $this->getEntityManager()->persist($purchaseOrderItem);
    }

    public function remove(PurchaseOrderItem $purchaseOrderItem): void
    {
        $this->getEntityManager()->remove($purchaseOrderItem);
    }

    public function get(PurchaseOrderItemId $id): ?PurchaseOrderItem
    {
        return $this->find($id->value());
    }

    public function getByPublicId(PurchaseOrderItemPublicId $publicId): ?PurchaseOrderItem
    {
        return $this->findOneBy(['publicId' => $publicId->value()]);
    }

    public function findPurchaseOrderItemsByStatus(
        Supplier $supplier,
        PurchaseOrderStatus $status,
        int $limit = 10,
    ): array {
        return $this->createQueryBuilder('poi')
            ->leftjoin('poi.purchaseOrder', 'po')
            ->leftjoin('po.customerOrder', 'co')
            ->where('po.supplier = :supplier')
            ->andWhere('poi.status = :status')
            ->andWhere('co.orderLock IS NULL')
            ->setParameter('supplier', $supplier)
            ->setParameter('status', $status)
            ->orderBy('poi.createdAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function calculateProductSales(\DateTime $startDate, \DateTime $endDate): array
    {
        return $this->createQueryBuilder('poi')
            ->select('p.id AS productId, s.id AS supplierId')
            ->addSelect('SUM(poi.quantity) AS salesQty')
            ->addSelect('SUM(poi.quantity * coi.price) AS salesValue')
            ->addSelect('SUM(poi.totalPrice) AS salesCost')
            ->join('poi.customerOrderItem', 'coi')
            ->join('poi.purchaseOrder', 'po')
            ->join('po.supplier', 's')
            ->join('coi.product', 'p')
            ->andWhere('poi.status = :status')
            ->setParameter('status', PurchaseOrderStatus::DELIVERED)
            ->andWhere('poi.deliveredAt between :startDate and :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('productId, supplierId')
            ->getQuery()
            ->getResult();
    }
}
