<?php

namespace App\Repository;

use App\DTO\SearchDto\SearchInterface;
use App\Entity\PurchaseOrder;
use App\Entity\Supplier;
use App\Enum\PurchaseOrderStatus;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PurchaseOrder>
 *
 * @method PurchaseOrder|null find($id, $lockMode = null, $lockVersion = null)
 * @method PurchaseOrder|null findOneBy(array $criteria, array $orderBy = null)
 * @method PurchaseOrder[]    findAll()
 * @method PurchaseOrder[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PurchaseOrderRepository extends ServiceEntityRepository implements SearchQueryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PurchaseOrder::class);
    }

    public function findBySearchDto(SearchInterface $searchDto): QueryBuilder
    {
        $sort = $searchDto->getSort() ?: $searchDto::SORT_DEFAULT;
        $sortDirection = $searchDto->getSortDirection() ?: $searchDto::SORT_DIRECTION_DEFAULT;

        $qb = $this->createQueryBuilder('p');

        if ($searchDto->getQuery()) {
            $qb->andWhere('p.id LIKE :query')
                ->setParameter('query', '%' . $searchDto->getQuery() . '%');
        }

        if ($searchDto->getPurchaseOrderId()) {
            $qb->andWhere('p.id = :purchaseOrderId')
                ->setParameter('purchaseOrderId', $searchDto->getPurchaseOrderId());
        }

        if ($searchDto->getCustomerOrderId()) {
            $qb->andWhere('p.customerOrder = :customerOrderId')
                ->setParameter('customerOrderId', $searchDto->getCustomerOrderId());
        }

        if ($searchDto->getCustomerId()) {
            $qb->leftJoin('p.customerOrder', 'o')
                ->andWhere('o.customer = :customerId')
                ->setParameter('customerId', $searchDto->getCustomerId());
        }

        if ($searchDto->getProductId()) {
            $qb->leftJoin('p.purchaseOrderItems', 'pi')
                ->leftJoin('pi.customerOrderItem', 'oi')
                ->andWhere('oi.product = :productId')
                ->setParameter('productId', $searchDto->getproductId());
        }

        if ($searchDto->getSupplierId()) {
            $qb->andWhere('p.supplier = :supplierId')
                ->setParameter('supplierId', $searchDto->getSupplierId());
        }

        if ($searchDto->getPurchaseOrderStatus()) {
            $qb->andWhere('p.status = :status')
                ->setParameter('status', $searchDto->getPurchaseOrderStatus());
        }

        if ($searchDto->getStartDate()) {
            $startDate = \DateTime::createFromFormat('Y-m-d', $searchDto->getStartDate());
            if ($startDate) {
                $qb->andWhere('p.createdAt >= :startDate')
                    ->setParameter('startDate', $startDate->format('Y-m-d'));
            }
        }

        if ($searchDto->getEndDate()) {
            $endDate = \DateTime::createFromFormat('Y-m-d', $searchDto->getEndDate());
            if ($endDate) {
                $qb->andWhere('p.createdAt <= :endDate')
                    ->setParameter('endDate', $endDate->format('Y-m-d'));
            }
        }

        $qb->orderBy('p.'.$sort, $sortDirection);

        return $qb;
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

    public function findRejectedPoSummary(DateTime $startDate): array
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
}
