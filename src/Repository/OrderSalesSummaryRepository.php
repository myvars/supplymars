<?php

namespace App\Repository;

use App\Entity\OrderSalesSummary;
use App\Enum\SalesDuration;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OrderSalesSummary>
 */
class OrderSalesSummaryRepository extends ServiceEntityRepository
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
            ->andWhere('os.duration = :duration');

    }

    public function deleteByDuration(string $duration, ?string $dateString): void
    {
        $qb = $this->createQueryBuilder('os')
            ->delete()
            ->where('os.duration = :duration')
            ->setParameter('duration', $duration);

        if ($dateString !== null) {
            $qb->andWhere('os.dateString = :dateString')->setParameter('dateString', $dateString);
        }

        $qb->getQuery()->execute();
    }
}