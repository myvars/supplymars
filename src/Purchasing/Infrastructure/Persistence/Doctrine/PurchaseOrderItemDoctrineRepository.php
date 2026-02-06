<?php

namespace App\Purchasing\Infrastructure\Persistence\Doctrine;

use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItem;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItemId;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItemPublicId;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;
use App\Purchasing\Domain\Model\Supplier\Supplier;
use App\Purchasing\Domain\Repository\PurchaseOrderItemRepository;
use App\Reporting\Application\Report\PoItemPerformanceReportCriteria;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Adapter\AdapterInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;

/**
 * @extends ServiceEntityRepository<PurchaseOrderItem>
 *
 * @method PurchaseOrderItem|null find($id, $lockMode = null, $lockVersion = null)
 * @method PurchaseOrderItem|null findOneBy(array<string, mixed> $criteria, ?array<string, string> $orderBy = null)
 * @method PurchaseOrderItem[]    findAll()
 * @method PurchaseOrderItem[]    findBy(array<string, mixed> $criteria, ?array<string, string> $orderBy = null, $limit = null, $offset = null)
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

    /**
     * @return array<int, array<string, mixed>>
     */
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

    /**
     * @return AdapterInterface<PurchaseOrderItem>
     */
    public function findForPerformanceReport(PoItemPerformanceReportCriteria $criteria): AdapterInterface
    {
        $sort = $criteria->getSort();
        $sortDirection = $criteria->getSortDirection();

        $qb = $this->getPerformanceReportQueryBuilder(
            new \DateTime($criteria->getDuration()->getStartDate()),
            new \DateTime($criteria->getDuration()->getEndDate())
        );

        // Always select profit for secondary sorting
        $qb->addSelect('(poi.quantity * coi.price) - poi.totalPrice AS HIDDEN profit');

        if ($sort === 'profit') {
            $qb->orderBy('profit', $sortDirection);
        } elseif ($sort === 'product.name') {
            $qb->join('coi.product', 'p')
                ->orderBy('p.name', $sortDirection)
                ->addOrderBy('profit', 'DESC');
        } elseif ($sort === 'supplier.name') {
            $qb->join('po.supplier', 's')
                ->orderBy('s.name', $sortDirection)
                ->addOrderBy('profit', 'DESC');
        } else {
            $qb->orderBy('poi.' . $sort, $sortDirection)
                ->addOrderBy('profit', 'DESC');
        }

        return new QueryAdapter($qb);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findPerformanceReportSummary(\DateTime $startDate, \DateTime $endDate): ?array
    {
        return $this->getPerformanceReportQueryBuilder($startDate, $endDate)
            ->select('COUNT(poi.id) AS itemCount')
            ->addSelect('SUM((poi.quantity * coi.price) - poi.totalPrice) AS totalProfit')
            ->getQuery()
            ->getOneOrNullResult();
    }

    private function getPerformanceReportQueryBuilder(\DateTime $startDate, \DateTime $endDate): QueryBuilder
    {
        return $this->createQueryBuilder('poi')
            ->join('poi.customerOrderItem', 'coi')
            ->join('poi.purchaseOrder', 'po')
            ->andWhere('poi.createdAt BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->andWhere('poi.status NOT IN (:excludedStatuses)')
            ->setParameter('excludedStatuses', [PurchaseOrderStatus::CANCELLED, PurchaseOrderStatus::REFUNDED]);
    }
}
