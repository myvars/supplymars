<?php

namespace App\Repository;

use App\Entity\SupplierCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SupplierCategory>
 *
 * @method SupplierCategory|null find($id, $lockMode = null, $lockVersion = null)
 * @method SupplierCategory|null findOneBy(array $criteria, array $orderBy = null)
 * @method SupplierCategory[]    findAll()
 * @method SupplierCategory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SupplierCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SupplierCategory::class);
    }

    //    /**
    //     * @return SupplierCategory[] Returns an array of SupplierCategory objects
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

    //    public function findOneBySomeField($value): ?SupplierCategory
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
