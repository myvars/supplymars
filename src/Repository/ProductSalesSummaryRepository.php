<?php

namespace App\Repository;

use App\Entity\ProductSales;
use App\Entity\ProductSalesSummary;
use App\Enum\SalesDuration;
use App\Enum\SalesType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProductSales>
 */
class ProductSalesSummaryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductSalesSummary::class);
    }

    public function findProductSalesSummary(int $salesTypeId, SalesType $salesType, SalesDuration $duration): ?array
    {
        return $this->getProductSalesSummaryQuery($salesTypeId, $salesType)
            ->setParameter('duration', $duration->value)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findProductSalesSummaryRange(
        string $salesTypeId,
        SalesType $salesType,
        SalesDuration $duration,
        string $startDate
    ): array {
        return $this->getProductSalesSummaryQuery($salesTypeId, $salesType)
            ->setParameter('duration', $duration->value)
            ->andWhere('ps.salesDate >= :startDate')
            ->setParameter('startDate', $startDate)
            ->orderBy('ps.salesDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getProductSalesSummaryQuery(int $salesTypeId, SalesType $salesType): QueryBuilder
    {
        return $this->createQueryBuilder('ps')
            ->select('ps.salesDate')
            ->addSelect('ps.salesQty')
            ->addSelect('ps.salesValue')
            ->addSelect('ps.salesCost')
            ->addSelect('(ps.salesValue - ps.salesCost) AS salesProfit')
            ->andWhere('ps.salesId = :salesId')
            ->andWhere('ps.salesType = :salesType')
            ->andWhere('ps.duration = :duration')
            ->setParameter('salesId', $salesTypeId)
            ->setParameter('salesType', $salesType->value);

    }

    public function deleteBySalesTypeAndDuration(string $salesType, string $duration, ?string $dateString): void
    {
        $qb = $this->createQueryBuilder('p')
            ->delete()
            ->where('p.salesType = :salesType')
            ->andWhere('p.duration = :duration')
            ->setParameter('salesType', $salesType)
            ->setParameter('duration', $duration);

        if ($dateString !== null) {
            $qb->andWhere('p.dateString = :dateString')->setParameter('dateString', $dateString );
        }

        $qb->getQuery()->execute();
    }
}