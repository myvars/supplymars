<?php

namespace App\Reporting\Infrastructure\Persistence\Doctrine;

use App\Reporting\Application\Report\ProductSalesReportCriteria;
use App\Reporting\Domain\Model\SalesType\ProductSales;
use App\Reporting\Domain\Model\SalesType\ProductSalesType;
use App\Reporting\Domain\Repository\ProductSalesRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProductSales>
 */
class ProductSalesDoctrineRepository extends ServiceEntityRepository implements ProductSalesRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductSales::class);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findByCriteria(ProductSalesReportCriteria $criteria): array
    {
        $qb = $this->getProductSalesQuery(
            $criteria->getDuration()->getStartDate(),
            $criteria->getDuration()->getEndDate()
        )->addSelect('p.id, p.publicId, p.name');

        if ($criteria->productId) {
            $qb->andWhere('ps.product = :productId')
                ->setParameter('productId', $criteria->productId);
        }

        if ($criteria->categoryId) {
            $qb->andWhere('p.category = :categoryId')
                ->setParameter('categoryId', $criteria->categoryId);
        }

        if ($criteria->subcategoryId) {
            $qb->andWhere('p.subcategory = :subcategoryId')
                ->setParameter('subcategoryId', $criteria->subcategoryId);
        }

        if ($criteria->manufacturerId) {
            $qb->andWhere('p.manufacturer = :manufacturerId')
                ->setParameter('manufacturerId', $criteria->manufacturerId);
        }

        if ($criteria->supplierId) {
            $qb->andWhere('ps.supplier = :supplierId')
                ->setParameter('supplierId', $criteria->supplierId);
        }

        return $qb
            ->groupBy('p.id')
            ->orderBy($criteria->getSort(), $criteria->getSortDirection())
            ->setMaxResults($criteria->getLimit())
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findLatestProductSales(ProductSalesType $productSalesType, int $limit = 10): array
    {
        $qb = $this->getProductSalesQuery(
            $productSalesType->getDuration()->getStartDate(),
            $productSalesType->getDuration()->getEndDate()
        )->addSelect('p.id, p.publicId, p.name');

        return $qb
            ->groupBy('p.id')
            ->orderBy('salesQty', 'desc')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findProductSalesSummary(ProductSalesType $productSalesType): array
    {
        $qb = $this->getProductSalesQuery($productSalesType->getStartDate(), $productSalesType->getEndDate())
            ->addSelect('DATE_FORMAT(ps.salesDate, :dateString) AS dateString')
            ->setParameter('dateString', $productSalesType->getDateString())
            ->groupBy('dateString, salesId');

        $salesTypeValue = $productSalesType->getSalesType()->value;
        match ($salesTypeValue) {
            'product' => $qb->addSelect('p.id AS salesId, p.name'),
            'category' => $qb->join('p.category', 'c')->addSelect('c.id AS salesId, c.name'),
            'subcategory' => $qb->join('p.subcategory', 's')->addSelect('s.id AS salesId, s.name'),
            'manufacturer' => $qb->join('p.manufacturer', 'm')->addSelect('m.id AS salesId, m.name'),
            'supplier' => $qb->join('ps.supplier', 's')->addSelect('s.id AS salesId, s.name'),
            'all' => $qb->addSelect("1 AS salesId, 'all' AS name"),
            default => throw new \InvalidArgumentException('Unknown entity: ' . $salesTypeValue),
        };

        return $qb->getQuery()
            ->getResult();
    }

    private function getProductSalesQuery(string $startDate, string $endDate): QueryBuilder
    {
        return $this->createQueryBuilder('ps')
            ->select('SUM(ps.salesQty) AS salesQty')
            ->addSelect('SUM(ps.salesValue) AS salesValue')
            ->addSelect('SUM(ps.salesCost) AS salesCost')
            ->addSelect('SUM(ps.salesValue - ps.salesCost) AS salesProfit')
            ->addSelect('AVG(CASE WHEN ps.salesCost > 0 THEN ((ps.salesValue - ps.salesCost) / ps.salesCost)*100 ELSE 0 END) AS salesMargin')
            ->join('ps.product', 'p')
            ->andWhere('ps.salesDate between :startDate and :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findProductSalesRange(int $productId, string $startDate, string $endDate): array
    {
        return $this->getProductSalesQuery($startDate, $endDate)
            ->addSelect('ps.salesDate')
            ->andWhere('ps.product = :productId')
            ->setParameter('productId', $productId)
            ->groupBy('ps.salesDate')
            ->orderBy('ps.salesDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function deleteByDate(string $date): void
    {
        $this->createQueryBuilder('p')
            ->delete()
            ->where('p.dateString = :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->execute();
    }
}
