<?php

namespace App\Reporting\Infrastructure\Persistence\Doctrine;

use App\Reporting\Domain\Model\SalesType\CustomerActivitySales;
use App\Reporting\Domain\Model\SalesType\CustomerSales;
use App\Reporting\Domain\Model\SalesType\CustomerSalesType;
use App\Reporting\Domain\Repository\CustomerSalesRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CustomerSales>
 */
class CustomerSalesDoctrineRepository extends ServiceEntityRepository implements CustomerSalesRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CustomerSales::class);
    }

    public function add(CustomerSales $customerSales): void
    {
        $this->getEntityManager()->persist($customerSales);
    }

    public function deleteByDate(string $date): void
    {
        $this->createQueryBuilder('cs')
            ->delete()
            ->where('cs.dateString = :date')
            ->setParameter('date', $date)
            ->getQuery()->execute();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findCustomerActivitySummary(CustomerSalesType $customerSalesType): array
    {
        return $this->getCustomerActivityQuery($customerSalesType->getStartDate(), $customerSalesType->getEndDate())
            ->addSelect('DATE_FORMAT(cas.salesDate, :dateString) AS dateString')
            ->setParameter('dateString', $customerSalesType->getDateString())
            ->groupBy('dateString')
            ->getQuery()->getResult();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findCustomerActivitySummaryRange(string $startDate, string $endDate): array
    {
        $activityRepo = $this->getEntityManager()->getRepository(CustomerActivitySales::class);

        return $activityRepo->createQueryBuilder('cas')
            ->select('cas.salesDate')
            ->addSelect('cas.totalCustomers')
            ->addSelect('cas.activeCustomers')
            ->addSelect('cas.newCustomers')
            ->addSelect('cas.returningCustomers')
            ->andWhere('cas.salesDate BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('cas.salesDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    private function getCustomerActivityQuery(string $startDate, string $endDate): QueryBuilder
    {
        $activityRepo = $this->getEntityManager()->getRepository(CustomerActivitySales::class);

        return $activityRepo->createQueryBuilder('cas')
            ->select('SUM(cas.activeCustomers) AS activeCustomers')
            ->addSelect('SUM(cas.newCustomers) AS newCustomers')
            ->addSelect('SUM(cas.returningCustomers) AS returningCustomers')
            ->addSelect('SUM(cas.totalCustomers) AS totalCustomers')
            ->andWhere('cas.salesDate between :startDate and :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate);
    }
}
