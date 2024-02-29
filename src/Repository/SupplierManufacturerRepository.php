<?php

namespace App\Repository;

use App\Entity\SupplierManufacturer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SupplierManufacturer>
 *
 * @method SupplierManufacturer|null find($id, $lockMode = null, $lockVersion = null)
 * @method SupplierManufacturer|null findOneBy(array $criteria, array $orderBy = null)
 * @method SupplierManufacturer[]    findAll()
 * @method SupplierManufacturer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SupplierManufacturerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SupplierManufacturer::class);
    }

    //    /**
    //     * @return SupplierManufacturer[] Returns an array of SupplierManufacturer objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?SupplierManufacturer
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
