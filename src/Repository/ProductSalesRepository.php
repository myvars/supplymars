<?php

namespace App\Repository;

use App\DTO\ProductSalesFilterDto;
use App\Entity\ProductSales;
use App\Enum\SalesType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProductSales>
 */
class ProductSalesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductSales::class);
    }

    public function findProductSalesBySalesDto(ProductSalesFilterDto $salesFilterDto): array
    {
        $qb = $this->getProductSalesQuery(
            $salesFilterDto->getDuration()->getStartDate(),
            $salesFilterDto->getDuration()->getEndDate()
        )->addSelect('p.id, p.name');

        if ($salesFilterDto->getProductId()) {
            $qb->andWhere('ps.product = :productId')
                ->setParameter('productId', $salesFilterDto->getProductId());
        }

        if ($salesFilterDto->getCategoryId()) {
            $qb->andWhere('p.category = :categoryId')
                ->setParameter('categoryId', $salesFilterDto->getCategoryId());
        }

        if ($salesFilterDto->getSubcategoryId()) {
            $qb->andWhere('p.subcategory = :subcategoryId')
                ->setParameter('subcategoryId', $salesFilterDto->getSubcategoryId());
        }

        if ($salesFilterDto->getManufacturerId()) {
            $qb->andWhere('p.manufacturer = :manufacturerId')
                ->setParameter('manufacturerId', $salesFilterDto->getManufacturerId());
        }

        if ($salesFilterDto->getSupplierId()) {
            $qb->andWhere('ps.supplier = :supplierId')
                ->setParameter('supplierId', $salesFilterDto->getSupplierId());
        }

        return $qb
            ->groupBy('p.id')
            ->orderBy($salesFilterDto->getSort()->value, $salesFilterDto->getSortDirection())
            ->setMaxResults($salesFilterDto::LIMIT_DEFAULT)
            ->getQuery()->getResult();
    }

    public function calculateSalesBySalesType(
        SalesType $salesType,
        string $startDate,
        string $endDate,
        string $dateString
    ): array
    {
        $qb = $this->getProductSalesQuery($startDate, $endDate)
            ->addSelect("DATE_FORMAT(ps.salesDate, :dateString) AS dateString")
            ->setParameter('dateString', $dateString)
            ->groupBy('dateString, salesId');

        match ($salesType->value) {
            'product' => $qb->addSelect('p.id AS salesId, p.name'),
            'category' => $qb->join('p.category', 'c')->addSelect('c.id AS salesId, c.name'),
            'subcategory' => $qb->join('p.subcategory', 's')->addSelect('s.id AS salesId, s.name'),
            'manufacturer' => $qb->join('p.manufacturer', 'm')->addSelect('m.id AS salesId, m.name'),
            'supplier' => $qb->join('ps.supplier', 's')->addSelect('s.id AS salesId, s.name'),
            default => throw new \InvalidArgumentException('Unknown entity: ' . $salesType->value),
        };

        return $qb->getQuery()->getResult();
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

    public function findProductSalesRange(int $productId, string $startDate, string $endDate): array
    {
        return $this->getProductSalesQuery($startDate, $endDate)
            ->addSelect('ps.salesDate')
            ->andWhere('ps.product = :productId')
            ->setParameter('productId', $productId)
            ->groupBy('ps.salesDate')
            ->orderBy('ps.salesDate', 'ASC')
            ->getQuery()->getResult();
    }

    public function deleteByDate(string $date): void
    {
        $this->createQueryBuilder('p')
            ->delete()
            ->where('p.dateString = :date')
            ->setParameter('date', $date)
            ->getQuery()->execute();
    }
}
