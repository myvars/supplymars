<?php

namespace App\Reporting\Infrastructure\Persistence\Doctrine;

use App\Reporting\Domain\Model\SalesType\OrderSales;
use App\Reporting\Domain\Model\SalesType\OrderSalesType;
use App\Reporting\Domain\Repository\OrderSalesRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OrderSales>
 */
class OrderSalesDoctrineRepository extends ServiceEntityRepository implements OrderSalesRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrderSales::class);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findOrderSalesSummary(OrderSalesType $orderSalesType): array
    {
        return $this->getOrderSalesQuery($orderSalesType->getStartDate(), $orderSalesType->getEndDate())
            ->addSelect('DATE_FORMAT(os.salesDate, :dateString) AS dateString')
            ->setParameter('dateString', $orderSalesType->getDateString())
            ->groupBy('dateString')
            ->getQuery()->getResult();
    }

    private function getOrderSalesQuery(string $startDate, string $endDate): QueryBuilder
    {
        return $this->createQueryBuilder('os')
            ->select('SUM(os.orderCount) AS orderCount')
            ->addSelect('SUM(os.orderValue) AS orderValue')
            ->addSelect('(SUM(os.orderValue) / SUM(os.orderCount)) AS averageOrderValue')
            ->andWhere('os.salesDate between :startDate and :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate);
    }

    public function deleteByDate(string $date): void
    {
        $this->createQueryBuilder('o')
            ->delete()
            ->where('o.dateString = :date')
            ->setParameter('date', $date)
            ->getQuery()->execute();
    }
}
