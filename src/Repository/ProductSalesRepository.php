<?php

namespace App\Repository;

use App\DTO\ProductSalesFilterDto;
use App\Entity\ProductSales;
use DateTime;
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
        $sort = $salesFilterDto->getSort() ?: $salesFilterDto::SORT_DEFAULT;
        $sortDirection = $salesFilterDto->getSortDirection() ?: $salesFilterDto::SORT_DIRECTION_DEFAULT;
        $startDate = $salesFilterDto->getDuration()->getStartDate();
        $endDate = $salesFilterDto->getDuration()->getEndDate();

        if ($salesFilterDto->getStartDate()) {
            $startDate = DateTime::createFromFormat('Y-m-d', $salesFilterDto->getStartDate())
                ->format('Y-m-d');
        }

        if ($salesFilterDto->getEndDate()) {
            $endDate = DateTime::createFromFormat('Y-m-d', $salesFilterDto->getEndDate())
                ->format('Y-m-d');
        }

        $qb = $this->getProductSalesQuery($startDate, $endDate)->addSelect('p.id, p.name');

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
            ->orderBy($sort, $sortDirection)
            ->setMaxResults($salesFilterDto::LIMIT_DEFAULT)
            ->getQuery()->getResult();
    }

    public function calculateSalesBySalesType(
        string $salesType,
        string $startDate,
        string $endDate,
        string $dateString
    ): array {
        $qb = $this->getProductSalesQuery($startDate, $endDate)
            ->addSelect("DATE_FORMAT(ps.salesDate, :dateString) AS dateString")
            ->setParameter('dateString', $dateString)
            ->groupBy('dateString, salesId');

        match ($salesType) {
            'product' => $qb->addSelect('p.id AS salesId, p.name'),
            'category' => $qb->join('p.category', 'c')->addSelect('c.id AS salesId, c.name'),
            'subcategory' => $qb->join('p.subcategory', 's')->addSelect('s.id AS salesId, s.name'),
            'manufacturer' => $qb->join('p.manufacturer', 'm')->addSelect('m.id AS salesId, m.name'),
            'supplier' => $qb->join('ps.supplier', 's')->addSelect('s.id AS salesId, s.name'),
            default => throw new \InvalidArgumentException('Unknown entity: ' . $salesType),
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
            ->join('ps.product', 'p')
            ->andWhere('ps.salesDate between :startDate and :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate);
    }

    public function deleteByDate(string $date): void
    {
        $qb = $this->createQueryBuilder('p')
            ->delete()
            ->where('p.dateString = :date')
            ->setParameter('date', $date);

        $qb->getQuery()->execute();
    }
}
