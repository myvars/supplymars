<?php

namespace App\Reporting\Infrastructure\Persistence\Doctrine;

use App\Reporting\Domain\Metric\SalesDuration;
use App\Reporting\Domain\Model\SalesType\OrderSalesSummary;
use App\Reporting\Domain\Model\SalesType\OrderSalesType;
use App\Reporting\Domain\Repository\OrderSalesSummaryRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OrderSalesSummary>
 */
class OrderSalesSummaryDoctrineRepository extends ServiceEntityRepository implements OrderSalesSummaryRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrderSalesSummary::class);
    }

    public function findOrderSalesSummary(SalesDuration $duration): ?array
    {
        return $this->getOrderSalesSummaryQuery()
            ->setParameter('duration', $duration->value)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOrderSalesSummaryRange(SalesDuration $duration, string $startDate): array
    {
        return $this->getOrderSalesSummaryQuery()
            ->setParameter('duration', $duration->value)
            ->andWhere('os.salesDate >= :startDate')
            ->setParameter('startDate', $startDate)
            ->orderBy('os.salesDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getOrderSalesSummaryQuery(): QueryBuilder
    {
        return $this->createQueryBuilder('os')
            ->select('os.salesDate')
            ->addSelect('os.orderCount')
            ->addSelect('os.orderValue')
            ->addSelect('os.averageOrderValue')
            ->andWhere('os.duration = :duration');
    }

    public function deleteByOrderSalesType(OrderSalesType $orderSalesType): void
    {
        $qb = $this->createQueryBuilder('os')
            ->delete()
            ->where('os.duration = :duration')
            ->setParameter('duration', $orderSalesType->getDuration()->value);

        if (null !== $orderSalesType->getRangeStartDate()) {
            $qb->andWhere('os.dateString = :dateString')
                ->setParameter('dateString', $orderSalesType->getRangeStartDate());
        }

        $qb->getQuery()->execute();
    }
}
