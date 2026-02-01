<?php

namespace App\Reporting\Infrastructure\Persistence\Doctrine;

use App\Reporting\Domain\Model\SalesType\CustomerActivitySales;
use App\Reporting\Domain\Repository\CustomerActivitySalesRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CustomerActivitySales>
 */
class CustomerActivitySalesDoctrineRepository extends ServiceEntityRepository implements CustomerActivitySalesRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CustomerActivitySales::class);
    }

    public function deleteByDate(string $date): void
    {
        $this->createQueryBuilder('cas')
            ->delete()
            ->where('cas.dateString = :date')
            ->setParameter('date', $date)
            ->getQuery()->execute();
    }
}
