<?php

namespace App\Purchasing\Infrastructure\Persistence\Doctrine;

use App\Purchasing\Application\Search\PurchaseOrderSearchCriteria;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrder;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderId;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderPublicId;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;
use App\Purchasing\Domain\Model\Supplier\Supplier;
use App\Purchasing\Domain\Repository\PurchaseOrderRepository;
use App\Shared\Application\Search\SearchCriteriaInterface;
use App\Shared\Infrastructure\Persistence\Search\FindByCriteriaInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ParameterType;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Adapter\AdapterInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;

/**
 * @extends ServiceEntityRepository<PurchaseOrder>
 *
 * @method PurchaseOrder|null find($id, $lockMode = null, $lockVersion = null)
 * @method PurchaseOrder|null findOneBy(array<string, mixed> $criteria, ?array<string, string> $orderBy = null)
 * @method PurchaseOrder[]    findAll()
 * @method PurchaseOrder[]    findBy(array<string, mixed> $criteria, ?array<string, string> $orderBy = null, $limit = null, $offset = null)
 */
class PurchaseOrderDoctrineRepository extends ServiceEntityRepository implements FindByCriteriaInterface, PurchaseOrderRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PurchaseOrder::class);
    }

    public function add(PurchaseOrder $purchaseOrder): void
    {
        $this->getEntityManager()->persist($purchaseOrder);
    }

    public function remove(PurchaseOrder $purchaseOrder): void
    {
        $this->getEntityManager()->remove($purchaseOrder);
    }

    public function get(PurchaseOrderId $id): ?PurchaseOrder
    {
        return $this->find($id->value());
    }

    public function getByPublicId(PurchaseOrderPublicId $publicId): ?PurchaseOrder
    {
        return $this->findOneBy(['publicId' => $publicId->value()]);
    }

    /**
     * @return AdapterInterface<PurchaseOrder>
     */
    public function findByCriteria(SearchCriteriaInterface $criteria): AdapterInterface
    {
        if (!$criteria instanceof PurchaseOrderSearchCriteria) {
            throw new \InvalidArgumentException('Expected PurchaseOrderSearchCriteria');
        }

        $sort = $criteria->getSort();
        $sortDirection = $criteria->getSortDirection();

        $qb = $this->createQueryBuilder('p');

        if ($criteria->getQuery()) {
            $qb->andWhere('p.id LIKE :query')
                ->setParameter('query', '%' . $criteria->getQuery() . '%');
        }

        if ($criteria->purchaseOrderId) {
            $qb->andWhere('p.id = :purchaseOrderId')
                ->setParameter('purchaseOrderId', $criteria->purchaseOrderId);
        }

        if ($criteria->orderId) {
            $qb->andWhere('p.customerOrder = :customerOrderId')
                ->setParameter('customerOrderId', $criteria->orderId);
        }

        if ($criteria->customerId) {
            $qb->leftJoin('p.customerOrder', 'o')
                ->andWhere('o.customer = :customerId')
                ->setParameter('customerId', $criteria->customerId);
        }

        if ($criteria->productId) {
            $qb->leftJoin('p.purchaseOrderItems', 'pi')
                ->leftJoin('pi.customerOrderItem', 'oi')
                ->andWhere('oi.product = :productId')
                ->setParameter('productId', $criteria->productId);
        }

        if ($criteria->supplierId) {
            $qb->andWhere('p.supplier = :supplierId')
                ->setParameter('supplierId', $criteria->supplierId);
        }

        if ($criteria->purchaseOrderStatus) {
            $qb->andWhere('p.status = :status')
                ->setParameter('status', $criteria->purchaseOrderStatus);
        }

        if ($criteria->startDate) {
            $startDate = \DateTime::createFromFormat('Y-m-d', $criteria->startDate);
            if ($startDate) {
                $qb->andWhere('p.createdAt >= :startDate')
                    ->setParameter('startDate', $startDate->format('Y-m-d'));
            }
        }

        if ($criteria->endDate) {
            $endDate = \DateTime::createFromFormat('Y-m-d', $criteria->endDate);
            if ($endDate) {
                $qb->andWhere('p.createdAt <= :endDate')
                    ->setParameter('endDate', $endDate->format('Y-m-d'));
            }
        }

        if (str_starts_with($sort, 'customerOrder.')) {
            $qb->leftJoin('p.customerOrder', 'customerOrder')->orderBy($sort, $sortDirection);
        } else {
            $qb->orderBy('p.' . $sort, $sortDirection);
        }

        return new QueryAdapter($qb);
    }

    public function findWaitingPurchaseOrders(Supplier $supplier, int $limit = 10): array
    {
        return $this->createQueryBuilder('po')
            ->leftjoin('po.customerOrder', 'co')
            ->where('po.supplier = :supplier')
            ->andWhere('po.status = :status')
            ->andWhere('co.orderLock IS NULL')
            ->setParameter('supplier', $supplier)
            ->setParameter('status', PurchaseOrderStatus::PROCESSING)
            ->orderBy('po.createdAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<string, mixed>
     */
    public function findRejectedPoSummary(\DateTime $startDate): array
    {
        return $this->createQueryBuilder('po')
            ->select('COUNT(po.id) as poCount')
            ->where('po.status = :status')
            ->setParameter('status', PurchaseOrderStatus::REJECTED)
            ->andWhere('po.createdAt >= :startDate')
            ->setParameter('startDate', $startDate)
            ->getQuery()
            ->getSingleResult();
    }

    public function countRejectedPurchaseOrders(): int
    {
        return $this->count(['status' => PurchaseOrderStatus::REJECTED]);
    }

    public function findByStatus(PurchaseOrderStatus $status, int $limit): array
    {
        return $this->findBy(['status' => $status], null, $limit);
    }

    public function findWithMixedItemStatusesIncludingRejected(int $daysBack = 30, int $limit = 100): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = <<<SQL
            SELECT po.id
            FROM purchase_order po
            JOIN purchase_order_item poi ON poi.purchase_order_id = po.id
            WHERE po.created_at >= DATE_SUB(NOW(), INTERVAL :daysBack DAY)
            GROUP BY po.id
            HAVING COUNT(DISTINCT poi.status) > 1
               AND SUM(poi.status = 'REJECTED') > 0
            ORDER BY po.created_at DESC
            LIMIT :limit
            SQL;

        $result = $conn->executeQuery($sql, [
            'daysBack' => $daysBack,
            'limit' => $limit,
        ], [
            'daysBack' => ParameterType::INTEGER,
            'limit' => ParameterType::INTEGER,
        ])->fetchAllAssociative();

        if ($result === []) {
            return [];
        }

        $ids = array_column($result, 'id');

        return $this->createQueryBuilder('po')
            ->where('po.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->orderBy('po.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
