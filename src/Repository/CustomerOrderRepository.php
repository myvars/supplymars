<?php

namespace App\Repository;

use App\DTO\SearchDto\OverdueOrderSearchDto;
use App\DTO\SearchDto\SearchInterface;
use App\Entity\CustomerOrder;
use App\Enum\OrderStatus;
use App\Enum\SalesDuration;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CustomerOrder>
 *
 * @method CustomerOrder|null find($id, $lockMode = null, $lockVersion = null)
 * @method CustomerOrder|null findOneBy(array $criteria, array $orderBy = null)
 * @method CustomerOrder[]    findAll()
 * @method CustomerOrder[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CustomerOrderRepository extends ServiceEntityRepository implements SearchQueryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CustomerOrder::class);
    }

    public function findBySearchDto(SearchInterface $searchDto): QueryBuilder
    {
        $sort = $searchDto->getSort() ?: $searchDto::SORT_DEFAULT;
        $sortDirection = $searchDto->getSortDirection() ?: $searchDto::SORT_DIRECTION_DEFAULT;

        $qb = $this->createQueryBuilder('o');

        if ($searchDto->getQuery()) {
            $qb->andWhere('o.id LIKE :query')
                ->setParameter('query', '%'.$searchDto->getQuery().'%');
        }

        if ($searchDto->getCustomerOrderId()) {
            $qb->andWhere('o.id = :customerOrderId')
                ->setParameter('customerOrderId', $searchDto->getCustomerOrderId());
        }

        if ($searchDto->getPurchaseOrderId()) {
            $qb->leftJoin('o.purchaseOrders', 'po')
                ->andWhere('po.id = :purchaseOrderId')
                ->setParameter('purchaseOrderId', $searchDto->getPurchaseOrderId());
        }

        if ($searchDto->getCustomerId()) {
            $qb->andWhere('o.customer = :customerId')
                ->setParameter('customerId', $searchDto->getCustomerId());
        }

        if ($searchDto->getProductId()) {
            $qb->leftJoin('o.customerOrderItems', 'oi')
                ->andWhere('oi.product= :productId')
                ->setParameter('productId', $searchDto->getproductId());
        }

        if ($searchDto->getOrderStatus()) {
            $qb->andWhere('o.status = :status')
                ->setParameter('status', $searchDto->getOrderStatus());
        }

        if ($searchDto->getStartDate()) {
            $startDate = \DateTime::createFromFormat('Y-m-d', $searchDto->getStartDate());
            if ($startDate) {
                $qb->andWhere('o.createdAt >= :startDate')
                    ->setParameter('startDate', $startDate->format('Y-m-d'));
            }
        }

        if ($searchDto->getEndDate()) {
            $endDate = \DateTime::createFromFormat('Y-m-d', $searchDto->getEndDate());
            if ($endDate) {
                $qb->andWhere('o.createdAt <= :endDate')
                    ->setParameter('endDate', $endDate->format('Y-m-d'));
            }
        }

        if (str_starts_with($sort, 'customer.')) {
            $qb->leftJoin('o.customer', 'customer')->orderBy($sort, $sortDirection);
        } else {
            $qb->orderBy('o.'.$sort, $sortDirection);
        }

        return $qb;
    }

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

    public function findOrderSalesByDate(DateTime $startDate, DateTime $endDate): array
    {
        return $this->getOrderSales($startDate, $endDate)
            ->addSelect('DATE_FORMAT(co.createdAt, :dateString) AS dateString')
            ->setParameter('dateString', '%Y-%m-%d')
            ->groupBy('dateString')
            ->getQuery()
            ->getResult();
    }

    public function findOrderSalesByStatus(DateTime $startDate, DateTime $endDate): array
    {
        return $this->getOrderSales($startDate, $endDate)
            ->addSelect('co.status')
            ->groupBy('co.status')
            ->getQuery()
            ->getResult();
    }

    public function getOrderSales(DateTime $startDate, DateTime $endDate): QueryBuilder
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

    public function findOverdueOrders(OverdueOrderSearchDto $dto): QueryBuilder
    {
        $sort = $dto->getSort() ?: $dto::SORT_DEFAULT;
        $sortDirection = $dto->getSortDirection() ?: $dto::SORT_DIRECTION_DEFAULT;
        $startDate = new DateTime($dto->getDuration()->getStartDate());

        $qb = $this->getOverdueOrders($startDate)
            ->select('co, DATE_DIFF(CURRENT_DATE(), co.dueDate) AS HIDDEN overdueDays');

        if (str_starts_with($sort, 'customer.')) {
            $qb->leftJoin('co.customer', 'customer')->orderBy($sort, $sortDirection);
        } else {
            $qb->orderBy('co.'.$sort, $sortDirection);
        }

        return $qb;
    }

    public function findOverdueOrdersSummary(DateTime $startDate): ?array
    {
        return $this->getOverdueOrders($startDate)
            ->select('COUNT(co.id) AS orderCount, SUM(co.totalPrice) AS orderValue')
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getOverdueOrders(DateTime $startDate): QueryBuilder
    {
        return $this->createQueryBuilder('co')
            ->andWhere('co.createdAt >= :startDate')
            ->setParameter('startDate', $startDate)
            ->andWhere('co.status NOT IN (:excludedStatuses)')
            ->setParameter('excludedStatuses', [OrderStatus::CANCELLED, OrderStatus::DELIVERED])
            ->andWhere('co.dueDate < CURRENT_DATE()');
    }

    public function findLatestOrders(DateTime $startDate, int $limit = 10): array
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
