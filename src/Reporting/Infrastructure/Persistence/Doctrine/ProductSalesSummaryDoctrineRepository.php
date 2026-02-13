<?php

namespace App\Reporting\Infrastructure\Persistence\Doctrine;

use App\Reporting\Domain\Metric\SalesDuration;
use App\Reporting\Domain\Metric\SalesType;
use App\Reporting\Domain\Model\SalesType\ProductSalesSummary;
use App\Reporting\Domain\Model\SalesType\ProductSalesType;
use App\Reporting\Domain\Repository\ProductSalesSummaryRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProductSalesSummary>
 */
class ProductSalesSummaryDoctrineRepository extends ServiceEntityRepository implements ProductSalesSummaryRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductSalesSummary::class);
    }

    public function add(ProductSalesSummary $productSalesSummary): void
    {
        $this->getEntityManager()->persist($productSalesSummary);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findProductSalesSummary(int $salesTypeId, SalesType $salesType, SalesDuration $duration): ?array
    {
        return $this->getProductSalesSummaryQuery($salesTypeId, $salesType)
            ->setParameter('duration', $duration->value)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findProductSalesSummaryRange(
        int $salesTypeId,
        SalesType $salesType,
        SalesDuration $duration,
        string $startDate,
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
            ->addSelect('(CASE WHEN ps.salesCost > 0 THEN ((ps.salesValue - ps.salesCost) / ps.salesCost)*100 ELSE 0 END) AS salesMargin')
            ->andWhere('ps.salesId = :salesId')
            ->andWhere('ps.salesType = :salesType')
            ->andWhere('ps.duration = :duration')
            ->setParameter('salesId', $salesTypeId)
            ->setParameter('salesType', $salesType->value);
    }

    public function deleteByProductSalesType(ProductSalesType $productSalesType): void
    {
        $qb = $this->createQueryBuilder('p')
            ->delete()
            ->where('p.salesType = :salesType')
            ->andWhere('p.duration = :duration')
            ->setParameter('salesType', $productSalesType->getSalesType()->value)
            ->setParameter('duration', $productSalesType->getDuration()->value);

        if (null !== $productSalesType->getRangeStartDate()) {
            $qb->andWhere('p.dateString = :dateString')
                ->setParameter('dateString', $productSalesType->getRangeStartDate());
        }

        $qb->getQuery()->execute();
    }
}
