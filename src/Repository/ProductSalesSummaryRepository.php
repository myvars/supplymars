<?php

namespace App\Repository;

use App\DTO\ProductSalesFilterDto;
use App\Entity\ProductSales;
use App\Entity\ProductSalesSummary;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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

    public function findProductSalesSummary(ProductSalesFilterDto $dto): ?array
    {
        $singleSalesType = $dto->getSingleSalesType();

        if ($singleSalesType === null) {
            return null;
        }

        return $this->createQueryBuilder('ps')
            ->select('ps.salesQty')
            ->addSelect('ps.salesValue')
            ->addSelect('ps.salesCost')
            ->addSelect('(ps.salesValue - ps.salesCost) AS salesProfit')
            ->andWhere('ps.salesId = :salesId')
            ->andWhere('ps.salesType = :salesType')
            ->andWhere('ps.duration = :duration')
            ->setParameter('salesId', $singleSalesType['salesTypeId'])
            ->setParameter('salesType', $singleSalesType['salesType'])
            ->setParameter('duration', $dto->getDuration())
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function deleteBySalesTypeAndDuration(string $salesType, string $duration): void
    {
        $qb = $this->createQueryBuilder('p')
            ->delete()
            ->where('p.salesType = :salesType')
            ->andWhere('p.duration = :duration')
            ->setParameter('salesType', $salesType)
            ->setParameter('duration', $duration);

        $qb->getQuery()->execute();
    }
}