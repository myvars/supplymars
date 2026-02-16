<?php

namespace App\Order\Infrastructure\Persistence\Doctrine;

use App\Order\Application\Search\OrderSearchCriteria;
use App\Order\Domain\Model\Order\CustomerOrder;
use App\Order\Domain\Model\Order\OrderId;
use App\Order\Domain\Model\Order\OrderPublicId;
use App\Order\Domain\Model\Order\OrderStatus;
use App\Order\Domain\Repository\OrderRepository;
use App\Reporting\Application\Report\OverdueOrderReportCriteria;
use App\Review\Domain\Model\Review\ReviewStatus;
use App\Shared\Application\Search\SearchCriteriaInterface;
use App\Shared\Infrastructure\Persistence\Search\FindByCriteriaInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Adapter\AdapterInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Symfony\Component\Uid\Ulid;

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
    public function findOverdueOrders(OverdueOrderReportCriteria $dto): AdapterInterface
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

    public function countOverdueOrders(): int
    {
        return (int) $this->getOverdueOrders(new \DateTime('-29 days'))
            ->select('COUNT(co.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countPendingOrders(): int
    {
        return $this->count(['status' => OrderStatus::PENDING]);
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

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findCustomerSalesByDate(\DateTime $startDate, \DateTime $endDate): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
            SELECT
                co.customer_id AS customerId,
                COUNT(co.id) AS orderCount,
                SUM(co.total_price_inc_vat) AS orderValue,
                COALESCE((
                    SELECT SUM(coi.quantity)
                    FROM customer_order_item coi
                    INNER JOIN customer_order co2 ON co2.id = coi.customer_order_id
                    WHERE co2.customer_id = co.customer_id
                    AND co2.status != :status
                    AND co2.created_at BETWEEN :startDate AND :endDate
                ), 0) AS itemCount
            FROM customer_order co
            WHERE co.status != :status
            AND co.created_at BETWEEN :startDate AND :endDate
            GROUP BY co.customer_id
        ';

        return $conn->fetchAllAssociative($sql, [
            'status' => OrderStatus::CANCELLED->value,
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function findCustomerActivityByDate(\DateTime $startDate, \DateTime $endDate): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
            SELECT
                COUNT(DISTINCT co.customer_id) AS activeCustomers,
                COUNT(DISTINCT CASE WHEN first_orders.first_order_date BETWEEN :startDate AND :endDate THEN co.customer_id END) AS newCustomers,
                COUNT(DISTINCT CASE WHEN lifetime.order_count > 1 AND first_orders.first_order_date < :startDate THEN co.customer_id END) AS returningCustomers
            FROM customer_order co
            INNER JOIN (
                SELECT customer_id, MIN(created_at) AS first_order_date
                FROM customer_order
                WHERE status != :cancelledStatus
                GROUP BY customer_id
            ) first_orders ON co.customer_id = first_orders.customer_id
            INNER JOIN (
                SELECT customer_id, COUNT(id) AS order_count
                FROM customer_order
                WHERE status != :cancelledStatus
                GROUP BY customer_id
            ) lifetime ON co.customer_id = lifetime.customer_id
            WHERE co.status != :cancelledStatus
            AND co.created_at BETWEEN :startDate AND :endDate
        ';

        $result = $conn->fetchAssociative($sql, [
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d H:i:s'),
            'cancelledStatus' => OrderStatus::CANCELLED->value,
        ]);

        return $result ?: ['activeCustomers' => 0, 'newCustomers' => 0, 'returningCustomers' => 0];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findTopCustomersByRevenue(\DateTime $startDate, \DateTime $endDate, int $limit = 10): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
            SELECT
                co.customer_id AS customerId,
                u.full_name AS fullName,
                u.public_id AS publicId,
                COUNT(co.id) AS orderCount,
                SUM(co.total_price_inc_vat) AS totalRevenue,
                SUM(co.total_price_inc_vat) / COUNT(co.id) AS averageOrderValue
            FROM customer_order co
            INNER JOIN user u ON u.id = co.customer_id
            WHERE co.status != :status
            AND co.created_at BETWEEN :startDate AND :endDate
            GROUP BY co.customer_id, u.full_name, u.public_id
            ORDER BY totalRevenue DESC
            LIMIT :resultLimit
        ';

        $results = $conn->fetchAllAssociative($sql, [
            'status' => OrderStatus::CANCELLED->value,
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d H:i:s'),
            'resultLimit' => $limit,
        ], [
            'resultLimit' => ParameterType::INTEGER,
        ]);

        return array_map(function (array $row): array {
            if (isset($row['publicId']) && is_string($row['publicId'])) {
                $row['publicId'] = Ulid::fromBinary($row['publicId'])->toBase32();
            }

            return $row;
        }, $results);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findCustomerGeographicSales(\DateTime $startDate, \DateTime $endDate): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
            SELECT
                a.city,
                COUNT(DISTINCT co.customer_id) AS customerCount,
                COUNT(co.id) AS orderCount,
                SUM(co.total_price_inc_vat) AS orderValue,
                CASE WHEN COUNT(co.id) > 0 THEN SUM(co.total_price_inc_vat) / COUNT(co.id) ELSE 0 END AS averageOrderValue
            FROM customer_order co
            INNER JOIN address a ON a.id = co.shipping_address_id
            WHERE co.status != :status
            AND co.created_at BETWEEN :startDate AND :endDate
            GROUP BY a.city
            ORDER BY orderValue DESC
        ';

        return $conn->fetchAllAssociative($sql, [
            'status' => OrderStatus::CANCELLED->value,
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findCustomerSegmentSales(\DateTime $startDate, \DateTime $endDate): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
            SELECT
                segment,
                COUNT(DISTINCT customer_id) AS customerCount,
                SUM(order_count) AS orderCount,
                SUM(order_value) AS orderValue,
                CASE WHEN SUM(order_count) > 0 THEN SUM(order_value) / SUM(order_count) ELSE 0 END AS averageOrderValue,
                CASE WHEN SUM(order_count) > 0 THEN SUM(item_count) / SUM(order_count) ELSE 0 END AS averageItemsPerOrder
            FROM (
                SELECT
                    co.customer_id,
                    COUNT(co.id) AS order_count,
                    SUM(co.total_price_inc_vat) AS order_value,
                    (SELECT COALESCE(SUM(coi.quantity), 0) FROM customer_order_item coi WHERE coi.customer_order_id IN (
                        SELECT co2.id FROM customer_order co2
                        WHERE co2.customer_id = co.customer_id
                        AND co2.status != :cancelledStatus
                        AND co2.created_at BETWEEN :startDate AND :endDate
                    )) AS item_count,
                    CASE
                        WHEN lifetime.order_count = 1 THEN :segNew
                        WHEN lifetime.order_count BETWEEN 2 AND 3 THEN :segReturning
                        WHEN lifetime.order_count >= 4 THEN :segLoyal
                        ELSE :segNew
                    END AS segment
                FROM customer_order co
                INNER JOIN (
                    SELECT customer_id, COUNT(id) AS order_count
                    FROM customer_order
                    WHERE status != :cancelledStatus
                    GROUP BY customer_id
                ) lifetime ON co.customer_id = lifetime.customer_id
                WHERE co.status != :cancelledStatus
                AND co.created_at BETWEEN :startDate AND :endDate
                GROUP BY co.customer_id, segment
            ) segmented
            GROUP BY segment

            UNION ALL

            SELECT
                :segLapsed AS segment,
                COUNT(DISTINCT lapsed.customer_id) AS customerCount,
                0 AS orderCount,
                0 AS orderValue,
                0 AS averageOrderValue,
                0 AS averageItemsPerOrder
            FROM (
                SELECT customer_id, MAX(created_at) AS last_order
                FROM customer_order
                WHERE status != :cancelledStatus
                AND created_at >= DATE_SUB(:startDate, INTERVAL 1 YEAR)
                GROUP BY customer_id
                HAVING MAX(created_at) < DATE_SUB(:startDate, INTERVAL 60 DAY)
            ) lapsed
        ';

        return $conn->fetchAllAssociative($sql, [
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d H:i:s'),
            'cancelledStatus' => OrderStatus::CANCELLED->value,
            'segNew' => 'new',
            'segReturning' => 'returning',
            'segLoyal' => 'loyal',
            'segLapsed' => 'lapsed',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function findRevenueMetrics(\DateTime $startDate, \DateTime $endDate): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
            SELECT
                COALESCE(SUM(co.total_price_inc_vat), 0) AS totalRevenue,
                COUNT(co.id) AS orderCount,
                CASE WHEN COUNT(co.id) > 0 THEN SUM(co.total_price_inc_vat) / COUNT(co.id) ELSE 0 END AS averageAov
            FROM customer_order co
            WHERE co.status != :cancelledStatus
            AND co.created_at BETWEEN :startDate AND :endDate
        ';

        $result = $conn->fetchAssociative($sql, [
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d H:i:s'),
            'cancelledStatus' => OrderStatus::CANCELLED->value,
        ]);

        return $result ?: ['totalRevenue' => '0.00', 'orderCount' => 0, 'averageAov' => '0.00'];
    }

    public function findLifetimeRevenue(): string
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
            SELECT COALESCE(SUM(co.total_price_inc_vat), 0) AS lifetimeRevenue
            FROM customer_order co
            WHERE co.status != :cancelledStatus
        ';

        return (string) ($conn->fetchOne($sql, ['cancelledStatus' => OrderStatus::CANCELLED->value]) ?: '0.00');
    }

    public function findReviewRate(\DateTime $startDate, \DateTime $endDate, int $activeCustomers): string
    {
        if ($activeCustomers === 0) {
            return '0.00';
        }

        $conn = $this->getEntityManager()->getConnection();

        $sql = '
            SELECT COUNT(DISTINCT pr.customer_id)
            FROM product_review pr
            WHERE pr.status = :publishedStatus
            AND pr.customer_id IN (
                SELECT DISTINCT co.customer_id
                FROM customer_order co
                WHERE co.status != :cancelledStatus
                AND co.created_at BETWEEN :startDate AND :endDate
            )
        ';

        $reviewingCustomers = (int) $conn->fetchOne($sql, [
            'publishedStatus' => ReviewStatus::PUBLISHED->value,
            'cancelledStatus' => OrderStatus::CANCELLED->value,
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d H:i:s'),
        ]);

        return number_format(($reviewingCustomers / $activeCustomers) * 100, 2, '.', '');
    }
}
