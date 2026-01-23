<?php

namespace App\Order\Infrastructure\Persistence\Doctrine;

use App\Order\Application\Search\OrderSearchCriteria;
use App\Order\Domain\Model\Order\CustomerOrder;
use App\Order\Domain\Model\Order\OrderId;
use App\Order\Domain\Model\Order\OrderPublicId;
use App\Order\Domain\Model\Order\OrderStatus;
use App\Order\Domain\Repository\OrderRepository;
use App\Reporting\Application\Search\OverdueOrderSearchCriteria;
use App\Shared\Application\Search\SearchCriteriaInterface;
use App\Shared\Infrastructure\Persistence\Search\FindByCriteriaInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Adapter\AdapterInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;

/**
 * @extends ServiceEntityRepository<CustomerOrder>
 *
 * @method CustomerOrder|null find($id, $lockMode = null, $lockVersion = null)
 * @method CustomerOrder|null findOneBy(array<string, mixed> $criteria, ?array<string, string> $orderBy = null)
 * @method CustomerOrder[]    findAll()
 * @method CustomerOrder[]    findBy(array<string, mixed> $criteria, ?array<string, string> $orderBy = null, $limit = null, $offset = null)
 */
class CustomerOrderDoctrineRepository extends ServiceEntityRepository implements FindByCriteriaInterface, OrderRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CustomerOrder::class);
    }

    public function add(CustomerOrder $order): void
    {
        $this->getEntityManager()->persist($order);
    }

    public function remove(CustomerOrder $order): void
    {
        $this->getEntityManager()->remove($order);
    }

    public function get(OrderId $id): ?CustomerOrder
    {
        return $this->find($id->value());
    }

    public function getByPublicId(OrderPublicId $publicId): ?CustomerOrder
    {
        return $this->findOneBy(['publicId' => $publicId->value()]);
    }

    /**
     * @return AdapterInterface<CustomerOrder>
     */
    public function findByCriteria(SearchCriteriaInterface $criteria): AdapterInterface
    {
        if (!$criteria instanceof OrderSearchCriteria) {
            throw new \InvalidArgumentException('Expected OrderSearchCriteria');
        }

        $sort = $criteria->getSort();
        $sortDirection = $criteria->getSortDirection();

        $qb = $this->createQueryBuilder('o');

        if ($criteria->getQuery()) {
            $qb->andWhere('o.id LIKE :query')
                ->setParameter('query', '%' . $criteria->getQuery() . '%');
        }

        if ($criteria->orderId) {
            $qb->andWhere('o.id = :customerOrderId')
                ->setParameter('customerOrderId', $criteria->orderId);
        }

        if ($criteria->purchaseOrderId) {
            $qb->leftJoin('o.purchaseOrders', 'po')
                ->andWhere('po.id = :purchaseOrderId')
                ->setParameter('purchaseOrderId', $criteria->purchaseOrderId);
        }

        if ($criteria->customerId) {
            $qb->andWhere('o.customer = :customerId')
                ->setParameter('customerId', $criteria->customerId);
        }

        if ($criteria->productId) {
            $qb->leftJoin('o.customerOrderItems', 'oi')
                ->andWhere('oi.product= :productId')
                ->setParameter('productId', $criteria->productId);
        }

        if ($criteria->orderStatus) {
            $qb->andWhere('o.status = :status')
                ->setParameter('status', $criteria->orderStatus);
        }

        if ($criteria->startDate) {
            $startDate = \DateTime::createFromFormat('Y-m-d', $criteria->startDate);
            if ($startDate) {
                $qb->andWhere('o.createdAt >= :startDate')
                    ->setParameter('startDate', $startDate->format('Y-m-d'));
            }
        }

        if ($criteria->endDate) {
            $endDate = \DateTime::createFromFormat('Y-m-d', $criteria->endDate);
            if ($endDate) {
                $qb->andWhere('o.createdAt <= :endDate')
                    ->setParameter('endDate', $endDate->format('Y-m-d'));
            }
        }

        if (str_starts_with($sort, 'customer.')) {
            $qb->leftJoin('o.customer', 'customer')->orderBy($sort, $sortDirection);
        } else {
            $qb->orderBy('o.' . $sort, $sortDirection);
        }

        return new QueryAdapter($qb);
    }

    /**
     * @return array<int, CustomerOrder>|null
     */
    public function findNextOrdersToBeProcessed(int $orderCount = 1): ?array
    {
        return $this->createQueryBuilder('co')
            ->leftJoin('co.purchaseOrders', 'po')
            ->andWhere('co.status = :status')
            ->andWhere('co.orderLock IS NULL')
            ->andWhere('po.id IS NULL')
            ->setParameter('status', OrderStatus::PENDING)
            ->orderBy('co.createdAt', 'ASC')
            ->setMaxResults($orderCount)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findOrderSalesByDate(\DateTime $startDate, \DateTime $endDate): array
    {
        return $this->getOrderSales($startDate, $endDate)
            ->addSelect('DATE_FORMAT(co.createdAt, :dateString) AS dateString')
            ->setParameter('dateString', '%Y-%m-%d')
            ->groupBy('dateString')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findOrderSalesByStatus(\DateTime $startDate, \DateTime $endDate): array
    {
        return $this->getOrderSales($startDate, $endDate)
            ->addSelect('co.status')
            ->groupBy('co.status')
            ->getQuery()
            ->getResult();
    }

    public function getOrderSales(\DateTime $startDate, \DateTime $endDate): QueryBuilder
    {
        return $this->createQueryBuilder('co')
            ->select('count(co.id) as orderCount')
            ->addSelect('SUM(co.totalPrice) AS orderValue')
            ->addSelect('SUM(co.totalPrice) / count(co.id) AS averageOrderValue')
            ->andWhere('co.status != :status')
            ->setParameter('status', OrderStatus::CANCELLED)
            ->andWhere('co.createdAt between :startDate and :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate);
    }

    /**
     * @return AdapterInterface<CustomerOrder>
     */
    public function findOverdueOrders(OverdueOrderSearchCriteria $dto): AdapterInterface
    {
        $sort = $dto->getSort();
        $sortDirection = $dto->getSortDirection();
        $startDate = new \DateTime($dto->getDuration()->getStartDate());

        $qb = $this->getOverdueOrders($startDate)
            ->select('co, DATE_DIFF(CURRENT_DATE(), co.dueDate) AS HIDDEN overdueDays');

        if (str_starts_with($sort, 'customer.')) {
            $qb->leftJoin('co.customer', 'customer')->orderBy($sort, $sortDirection);
        } else {
            $qb->orderBy('co.' . $sort, $sortDirection);
        }

        return new QueryAdapter($qb);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findOverdueOrdersSummary(\DateTime $startDate): ?array
    {
        return $this->getOverdueOrders($startDate)
            ->select('COUNT(co.id) AS orderCount, SUM(co.totalPrice) AS orderValue')
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getOverdueOrders(\DateTime $startDate): QueryBuilder
    {
        return $this->createQueryBuilder('co')
            ->andWhere('co.createdAt >= :startDate')
            ->setParameter('startDate', $startDate)
            ->andWhere('co.status NOT IN (:excludedStatuses)')
            ->setParameter('excludedStatuses', [OrderStatus::CANCELLED, OrderStatus::DELIVERED])
            ->andWhere('co.dueDate < CURRENT_DATE()');
    }

    /**
     * @return array<int, CustomerOrder>
     */
    public function findLatestOrders(\DateTime $startDate, int $limit = 10): array
    {
        return $this->createQueryBuilder('co')
            ->andWhere('co.createdAt >= :startDate')
            ->setParameter('startDate', $startDate)
            ->orderBy('co.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
