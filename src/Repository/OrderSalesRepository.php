<?php

namespace App\Repository;

use App\Entity\OrderSales;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OrderSales>
 */
class OrderSalesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrderSales::class);
    }

    public function calculateSales(string $startDate, string $endDate, string $dateString): array
    {
        return $this->getOrderSalesQuery($startDate, $endDate)
            ->addSelect("DATE_FORMAT(os.salesDate, :dateString) AS dateString")
            ->setParameter('dateString', $dateString)
            ->groupBy('dateString')
            ->getQuery()->getResult();
    }


    private function getOrderSalesQuery(string $startDate, string $endDate): QueryBuilder
    {
        return $this->createQueryBuilder('os')
            ->select('SUM(os.orderCount) AS orderCount')
            ->addSelect('SUM(os.orderValue) AS orderValue')
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
