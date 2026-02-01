<?php

namespace App\Reporting\Infrastructure\Persistence\Doctrine;

use App\Reporting\Domain\Metric\SalesDuration;
use App\Reporting\Domain\Model\SalesType\CustomerSalesSummary;
use App\Reporting\Domain\Model\SalesType\CustomerSalesType;
use App\Reporting\Domain\Repository\CustomerSalesSummaryRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CustomerSalesSummary>
 */
class CustomerSalesSummaryDoctrineRepository extends ServiceEntityRepository implements CustomerSalesSummaryRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CustomerSalesSummary::class);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findCustomerSalesSummary(SalesDuration $duration): ?array
    {
        return $this->getCustomerSalesSummaryQuery()
            ->setParameter('duration', $duration->value)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findCustomerSalesSummaryRange(SalesDuration $duration, string $startDate): array
    {
        return $this->getCustomerSalesSummaryQuery()
            ->setParameter('duration', $duration->value)
            ->andWhere('cs.salesDate >= :startDate')
            ->setParameter('startDate', $startDate)
            ->orderBy('cs.salesDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    private function getCustomerSalesSummaryQuery(): QueryBuilder
    {
        return $this->createQueryBuilder('cs')
            ->select('cs.salesDate')
            ->addSelect('cs.totalCustomers')
            ->addSelect('cs.activeCustomers')
            ->addSelect('cs.newCustomers')
            ->addSelect('cs.returningCustomers')
            ->addSelect('cs.totalRevenue')
            ->addSelect('cs.averageClv')
            ->addSelect('cs.averageAov')
            ->addSelect('cs.repeatRate')
            ->addSelect('cs.reviewRate')
            ->addSelect('cs.averageOrdersPerCustomer')
            ->andWhere('cs.duration = :duration');
    }

    public function deleteByCustomerSalesType(CustomerSalesType $customerSalesType): void
    {
        $qb = $this->createQueryBuilder('cs')
            ->delete()
            ->where('cs.duration = :duration')
            ->setParameter('duration', $customerSalesType->getDuration()->value);

        if (null !== $customerSalesType->getRangeStartDate()) {
            $qb->andWhere('cs.dateString = :dateString')
                ->setParameter('dateString', $customerSalesType->getRangeStartDate());
        }

        $qb->getQuery()->execute();
    }
}
