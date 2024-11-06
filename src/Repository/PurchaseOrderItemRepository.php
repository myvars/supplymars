<?php

namespace App\Repository;

use App\DTO\ProductSalesFilterDto;
use App\Entity\PurchaseOrderItem;
use App\Entity\Supplier;
use App\Enum\PurchaseOrderStatus;
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
class PurchaseOrderItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PurchaseOrderItem::class);
    }

    public function findBySalesDto(ProductSalesFilterDto $salesFilterDto): array
    {
        $sort = $salesFilterDto->getSort() ?: $salesFilterDto::SORT_DEFAULT;
        $duration = $salesFilterDto->getDuration() ?: $salesFilterDto::DURATION_DEFAULT;
        $sortDirection = $salesFilterDto->getSortDirection() ?: $salesFilterDto::SORT_DIRECTION_DEFAULT;

        $qb = $this->createQueryBuilder('poi')
            ->select('p.id, p.name')
            ->addSelect('SUM(poi.quantity) AS salesQuantity')
            ->addSelect('SUM(poi.totalPrice) AS salesValue')
            ->addSelect('SUM((poi.quantity * coi.price) - poi.totalPrice) AS salesProfit')
            ->join('poi.customerOrderItem', 'coi')
            ->join('coi.product' , 'p')
            ->andWhere('poi.status = :status')
            ->setParameter('status', PurchaseOrderStatus::DELIVERED);

        if ($salesFilterDto->getProductId()) {
            $qb->andWhere('coi.product = :productId')
                ->setParameter('productId', $salesFilterDto->getProductId());
        }

        if ($salesFilterDto->getCategoryId()) {
            $qb->andWhere('p.category = :categoryId')
                ->setParameter('categoryId', $salesFilterDto->getCategoryId());
        }

        if ($salesFilterDto->getSubcategoryId()) {
            $qb->andWhere('p.subcategory = :subcategoryId')
                ->setParameter('subcategoryId', $salesFilterDto->getSubcategoryId());
        }

        if ($salesFilterDto->getManufacturerId()) {
            $qb->andWhere('p.manufacturer = :manufacturerId')
                ->setParameter('manufacturerId', $salesFilterDto->getManufacturerId());
        }

        if ($salesFilterDto->getSupplierId()) {
            $qb->join('poi.purchaseOrder', 'po')
                ->andWhere('po.supplier = :supplierId')
                ->setParameter('supplierId', $salesFilterDto->getSupplierId());
        }

        if (!$salesFilterDto->getStartDate() && !$salesFilterDto->getEndDate()) {
            dump($duration, $this->getDurationStartDate($duration));
            $qb->andWhere('poi.deliveredAt >= :startDate')
                ->setParameter('startDate', $this->getDurationStartDate($duration));
        }

        if ($salesFilterDto->getStartDate()) {
            $startDate = \DateTime::createFromFormat('Y-m-d', $salesFilterDto->getStartDate());
            if ($startDate) {
                $qb->andWhere('poi.deliveredAt >= :startDate')
                    ->setParameter('startDate', $startDate->format('Y-m-d'));
            }
        }

        if ($salesFilterDto->getEndDate()) {
            $endDate = \DateTime::createFromFormat('Y-m-d', $salesFilterDto->getEndDate());
            if ($endDate) {
                $qb->andWhere('poi.deliveredAt <= :endDate')
                    ->setParameter('endDate', $endDate->format('Y-m-d'));
            }
        }

        $qb
            ->groupBy('p.id')
            ->orderBy($sort, $sortDirection)
            ->setMaxResults($salesFilterDto::LIMIT_DEFAULT)
            ->getQuery();
        dump($qb->getQuery()->getSQL());

            return $qb->getQuery()->getResult();
    }

    private function getDurationStartDate(string $duration): string
    {
        return match ($duration) {
            'last30' => (new \DateTime('-30 day'))->format('Y-m-d'),
            'last7' => (new \DateTime('-7 day'))->format('Y-m-d'),
            'today' => (new \DateTime())->format('Y-m-d'),
            'mtd' => (new \DateTime())->format('Y-m-01'),
            default => throw new \InvalidArgumentException('Invalid duration'),
        };
    }

    public function findPurchaseOrderItemsByStatus(
        Supplier $supplier,
        PurchaseOrderStatus $status,
        int $limit = 10
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
}
