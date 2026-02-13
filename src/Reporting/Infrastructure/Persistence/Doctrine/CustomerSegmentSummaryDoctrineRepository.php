<?php

namespace App\Reporting\Infrastructure\Persistence\Doctrine;

use App\Reporting\Domain\Metric\SalesDuration;
use App\Reporting\Domain\Model\SalesType\CustomerSalesType;
use App\Reporting\Domain\Model\SalesType\CustomerSegmentSummary;
use App\Reporting\Domain\Repository\CustomerSegmentSummaryRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CustomerSegmentSummary>
 */
class CustomerSegmentSummaryDoctrineRepository extends ServiceEntityRepository implements CustomerSegmentSummaryRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CustomerSegmentSummary::class);
    }

    public function add(CustomerSegmentSummary $customerSegmentSummary): void
    {
        $this->getEntityManager()->persist($customerSegmentSummary);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findSegmentSummary(SalesDuration $duration): array
    {
        return $this->createQueryBuilder('css')
            ->select('css.segment')
            ->addSelect('css.customerCount')
            ->addSelect('css.orderCount')
            ->addSelect('css.orderValue')
            ->addSelect('css.averageOrderValue')
            ->addSelect('css.averageItemsPerOrder')
            ->andWhere('css.duration = :duration')
            ->setParameter('duration', $duration->value)
            ->orderBy('css.orderValue', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function deleteByCustomerSalesType(CustomerSalesType $customerSalesType): void
    {
        $qb = $this->createQueryBuilder('css')
            ->delete()
            ->where('css.duration = :duration')
            ->setParameter('duration', $customerSalesType->getDuration()->value);

        if (null !== $customerSalesType->getRangeStartDate()) {
            $qb->andWhere('css.dateString = :dateString')
                ->setParameter('dateString', $customerSalesType->getRangeStartDate());
        }

        $qb->getQuery()->execute();
    }
}
