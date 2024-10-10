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

    public function findSupplierManufacturers(
        int $supplierId,
        ?int $supplierCategoryId,
        ?int $supplierSubcategoryId
    ): ?array {
        $qb = $this->createQueryBuilder('sm')
            ->leftJoin('sm.supplierProducts', 'sp')
            ->andWhere('sm.supplier = :supplierId')
            ->setParameter('supplierId', $supplierId);

        if ($supplierCategoryId) {
            $qb->andWhere('sp.supplierCategory = :supplierCategoryId')
                ->setParameter('supplierCategoryId', $supplierCategoryId);
        }

        if ($supplierSubcategoryId) {
            $qb->andWhere('sp.supplierSubcategory = :supplierSubcategoryId')
                ->setParameter('supplierSubcategoryId', $supplierSubcategoryId);
        }

        return $qb->getQuery()->getResult();
    }
}
