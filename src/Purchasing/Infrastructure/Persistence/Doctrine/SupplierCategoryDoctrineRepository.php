<?php

namespace App\Purchasing\Infrastructure\Persistence\Doctrine;

use App\Purchasing\Domain\Model\SupplierProduct\SupplierCategory;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierCategoryId;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierCategoryPublicId;
use App\Purchasing\Domain\Repository\SupplierCategoryRepository;
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
class SupplierCategoryDoctrineRepository extends ServiceEntityRepository implements SupplierCategoryRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SupplierCategory::class);
    }

    public function add(SupplierCategory $supplierCategory): void
    {
        $this->getEntityManager()->persist($supplierCategory);
    }

    public function remove(SupplierCategory $supplierCategory): void
    {
        $this->getEntityManager()->remove($supplierCategory);
    }

    public function get(SupplierCategoryId $id): ?SupplierCategory
    {
        return $this->find($id->value());
    }

    public function getByPublicId(SupplierCategoryPublicId $publicId): ?SupplierCategory
    {
        return $this->findOneBy(['publicId' => $publicId->value()]);
    }
}
