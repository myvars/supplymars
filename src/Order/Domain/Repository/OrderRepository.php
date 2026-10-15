<?php

declare(strict_types=1);

namespace App\Order\Domain\Repository;

use App\Order\Domain\Model\Order\CustomerOrder;
use App\Order\Domain\Model\Order\OrderId;
use App\Order\Domain\Model\Order\OrderPublicId;
use App\Order\Infrastructure\Persistence\Doctrine\CustomerOrderDoctrineRepository;
use App\Shared\Infrastructure\Persistence\Search\FindByCriteriaInterface;
use Pagerfanta\Adapter\AdapterInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(CustomerOrderDoctrineRepository::class)]
interface OrderRepository extends FindByCriteriaInterface
{
    public function add(CustomerOrder $order): void;

    public function remove(CustomerOrder $order): void;

    public function get(OrderId $id): ?CustomerOrder;

    public function getByPublicId(OrderPublicId $publicId): ?CustomerOrder;

    /** @return CustomerOrder[]|null */
    public function findNextOrdersToBeProcessed(int $orderCount = 1): ?array;

    public function countOverdueOrders(): int;

    public function countPendingOrders(): int;

    public function countDemoOrdersCreatedToday(): int;

    public function count(): int;

    /**
     * @return array<int, CustomerOrder>
     */
    public function findLatestOrders(\DateTime $startDate, int $limit = 10): array;

    /**
     * The caller checks $sort against its allowed fields.
     *
     * @return AdapterInterface<CustomerOrder>
     */
    public function findOverdueOrders(\DateTime $startDate, string $sort, string $sortDirection): AdapterInterface;

    /**
     * @return array<string, mixed>|null
     */
    public function findOverdueOrdersSummary(\DateTime $startDate): ?array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findOrderSalesByDate(\DateTime $startDate, \DateTime $endDate): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findOrderSalesByStatus(\DateTime $startDate, \DateTime $endDate): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findCustomerSalesByDate(\DateTime $startDate, \DateTime $endDate): array;

    /**
     * @return array<string, mixed>
     */
    public function findCustomerActivityByDate(\DateTime $startDate, \DateTime $endDate): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findCustomerGeographicSales(\DateTime $startDate, \DateTime $endDate): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findCustomerSegmentSales(\DateTime $startDate, \DateTime $endDate): array;

    /**
     * @return array<string, mixed>
     */
    public function findRevenueMetrics(\DateTime $startDate, \DateTime $endDate): array;

    public function findLifetimeRevenue(): string;

    public function findReviewRate(\DateTime $startDate, \DateTime $endDate, int $activeCustomers): string;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findTopCustomersByRevenue(\DateTime $startDate, \DateTime $endDate, int $limit = 10): array;
}
