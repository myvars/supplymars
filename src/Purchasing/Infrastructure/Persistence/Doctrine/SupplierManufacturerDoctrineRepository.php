<?php

namespace App\Purchasing\Infrastructure\Persistence\Doctrine;

use App\Purchasing\Domain\Model\SupplierProduct\SupplierManufacturer;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierManufacturerId;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierManufacturerPublicId;
use App\Purchasing\Domain\Repository\SupplierManufacturerRepository;
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
class SupplierManufacturerDoctrineRepository extends ServiceEntityRepository implements SupplierManufacturerRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SupplierManufacturer::class);
    }

    public function add(SupplierManufacturer $supplierManufacturer): void
    {
        $this->getEntityManager()->persist($supplierManufacturer);
    }

    public function remove(SupplierManufacturer $supplierManufacturer): void
    {
        $this->getEntityManager()->remove($supplierManufacturer);
    }

    public function get(SupplierManufacturerId $id): ?SupplierManufacturer
    {
        return $this->find($id->value());
    }

    public function getByPublicId(SupplierManufacturerPublicId $publicId): ?SupplierManufacturer
    {
        return $this->findOneBy(['publicId' => $publicId->value()]);
    }

    public function findSupplierManufacturers(
        ?int $supplierId,
        ?int $supplierCategoryId,
        ?int $supplierSubcategoryId,
    ): ?array {
        $qb = $this->createQueryBuilder('sm')
            ->leftJoin('sm.supplierProducts', 'sp');

        if ($supplierId) {
            $qb->andWhere('sm.supplier = :supplierId')
                ->setParameter('supplierId', $supplierId);
        }

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
