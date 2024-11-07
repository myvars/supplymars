<?php

namespace App\Repository;

use App\DTO\ProductSalesFilterDto;
use App\Entity\ProductSales;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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

    public function findBySalesDto(ProductSalesFilterDto $salesFilterDto): array
    {
        $sort = $salesFilterDto->getSort() ?: $salesFilterDto::SORT_DEFAULT;
        $duration = $salesFilterDto->getDuration() ?: $salesFilterDto::DURATION_DEFAULT;
        $sortDirection = $salesFilterDto->getSortDirection() ?: $salesFilterDto::SORT_DIRECTION_DEFAULT;

        $qb = $this->createQueryBuilder('ps')
            ->select('p.id, p.name')
            ->addSelect('SUM(ps.salesQty) AS salesQuantity')
            ->addSelect('SUM(ps.salesValue) AS salesValue')
            ->addSelect('SUM(ps.salesValue - ps.salesCost) AS salesProfit')
            ->join('ps.product' , 'p');

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

        if (!$salesFilterDto->getStartDate() && !$salesFilterDto->getEndDate()) {
            $qb->andWhere('ps.salesDate >= :startDate')
                ->setParameter('startDate', $this->getDurationStartDate($duration));
        }

        if ($salesFilterDto->getStartDate()) {
            $startDate = \DateTime::createFromFormat('Y-m-d', $salesFilterDto->getStartDate());
            if ($startDate) {
                $qb->andWhere('ps.salesDate >= :startDate')
                    ->setParameter('startDate', $startDate->format('Y-m-d'));
            }
        }

        if ($salesFilterDto->getEndDate()) {
            $endDate = \DateTime::createFromFormat('Y-m-d', $salesFilterDto->getEndDate());
            if ($endDate) {
                $qb->andWhere('ps.salesDate <= :endDate')
                    ->setParameter('endDate', $endDate->format('Y-m-d'));
            }
        }

        return $qb
            ->groupBy('p.id')
            ->orderBy($sort, $sortDirection)
            ->setMaxResults($salesFilterDto::LIMIT_DEFAULT)
            ->getQuery()
            ->getResult();
    }

    private function getDurationStartDate(string $duration): string
    {
        return match ($duration) {
            'last30' => (new \DateTime('-30 day'))->format('Y-m-d'),
            'last7' => (new \DateTime('-7 day'))->format('Y-m-d'),
            'today' => (new \DateTime())->format('Y-m-d'),
            'mtd' => (new \DateTime())->format('Y-m-01'),
            default => throw new \InvalidArgumentException('Invalid duration'),
        };
    }
}
