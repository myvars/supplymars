<?php

namespace App\Reporting\Infrastructure\Persistence\Doctrine;

use App\Reporting\Domain\Metric\SalesDuration;
use App\Reporting\Domain\Model\SalesType\CustomerGeographicSummary;
use App\Reporting\Domain\Model\SalesType\CustomerSalesType;
use App\Reporting\Domain\Repository\CustomerGeographicSummaryRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CustomerGeographicSummary>
 */
class CustomerGeographicSummaryDoctrineRepository extends ServiceEntityRepository implements CustomerGeographicSummaryRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CustomerGeographicSummary::class);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findGeographicSummary(SalesDuration $duration): array
    {
        return $this->createQueryBuilder('cgs')
            ->select('cgs.city')
            ->addSelect('cgs.customerCount')
            ->addSelect('cgs.orderCount')
            ->addSelect('cgs.orderValue')
            ->addSelect('cgs.averageOrderValue')
            ->andWhere('cgs.duration = :duration')
            ->setParameter('duration', $duration->value)
            ->orderBy('cgs.orderValue', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function deleteByCustomerSalesType(CustomerSalesType $customerSalesType): void
    {
        $qb = $this->createQueryBuilder('cgs')
            ->delete()
            ->where('cgs.duration = :duration')
            ->setParameter('duration', $customerSalesType->getDuration()->value);

        if (null !== $customerSalesType->getRangeStartDate()) {
            $qb->andWhere('cgs.dateString = :dateString')
                ->setParameter('dateString', $customerSalesType->getRangeStartDate());
        }

        $qb->getQuery()->execute();
    }
}
