<?php

namespace App\Repository;

use App\Entity\SupplierSubcategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SupplierSubcategory>
 *
 * @method SupplierSubcategory|null find($id, $lockMode = null, $lockVersion = null)
 * @method SupplierSubcategory|null findOneBy(array $criteria, array $orderBy = null)
 * @method SupplierSubcategory[]    findAll()
 * @method SupplierSubcategory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SupplierSubcategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SupplierSubcategory::class);
    }

    //    /**
    //     * @return SupplierSubcategory[] Returns an array of SupplierSubcategory objects
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

    //    public function findOneBySomeField($value): ?SupplierSubcategory
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
