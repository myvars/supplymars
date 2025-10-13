<?php

namespace App\Purchasing\Infrastructure\Persistence\Doctrine;

use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrder;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderId;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderPublicId;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;
use App\Purchasing\Domain\Model\Supplier\Supplier;
use App\Purchasing\Domain\Repository\PurchaseOrderRepository;
use App\Shared\Application\Search\SearchCriteriaInterface;
use App\Shared\Infrastructure\Persistence\Search\FindByCriteriaInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Adapter\AdapterInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;

/**
 * @extends ServiceEntityRepository<PurchaseOrder>
 *
 * @method PurchaseOrder|null find($id, $lockMode = null, $lockVersion = null)
 * @method PurchaseOrder|null findOneBy(array $criteria, array $orderBy = null)
 * @method PurchaseOrder[] findAll()
 * @method PurchaseOrder[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PurchaseOrderDoctrineRepository extends ServiceEntityRepository implements
    FindByCriteriaInterface,
    PurchaseOrderRepository
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

    public function findByCriteria(SearchCriteriaInterface $criteria): AdapterInterface
    {
        $sort = $criteria->getSort();
        $sortDirection = $criteria->getSortDirection();

        $qb = $this->createQueryBuilder('p');

        if ($criteria->getQuery()) {
            $qb->andWhere('p.id LIKE :query')
                ->setParameter('query', '%'.$criteria->getQuery().'%');
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
            $qb->orderBy('p.'.$sort, $sortDirection);
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
}
