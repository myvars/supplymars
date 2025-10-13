<?php

namespace App\Purchasing\Infrastructure\Persistence\Doctrine;

use App\Purchasing\Domain\Model\SupplierProduct\SupplierSubcategory;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierSubcategoryId;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierSubcategoryPublicId;
use App\Purchasing\Domain\Repository\SupplierSubcategoryRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SupplierSubcategory>
 *
 * @method SupplierSubcategory|null find($id, $lockMode = null, $lockVersion = null)
 * @method SupplierSubcategory|null findOneBy(array $criteria, array $orderBy = null)
 * @method SupplierSubcategory[] findAll()
 * @method SupplierSubcategory[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SupplierSubcategoryDoctrineRepository extends ServiceEntityRepository implements SupplierSubcategoryRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SupplierSubcategory::class);
    }

    public function add(SupplierSubcategory $supplierSubcategory): void
    {
        $this->getEntityManager()->persist($supplierSubcategory);
    }

    public function remove(SupplierSubcategory $supplierSubcategory): void
    {
        $this->getEntityManager()->remove($supplierSubcategory);
    }

    public function get(SupplierSubcategoryId $id): ?SupplierSubcategory
    {
        return $this->find($id->value());
    }

    public function getByPublicId(SupplierSubcategoryPublicId $publicId): ?SupplierSubcategory
    {
        return $this->findOneBy(['publicId' => $publicId->value()]);
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
