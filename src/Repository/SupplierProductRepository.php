<?php

namespace App\Repository;

use App\DTO\SearchDto\SearchInterface;
use App\Entity\Supplier;
use App\Entity\SupplierProduct;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SupplierProduct>
 *
 * @method SupplierProduct|null find($id, $lockMode = null, $lockVersion = null)
 * @method SupplierProduct|null findOneBy(array $criteria, array $orderBy = null)
 * @method SupplierProduct[]    findAll()
 * @method SupplierProduct[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SupplierProductRepository extends ServiceEntityRepository implements SearchQueryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SupplierProduct::class);
    }

    public function findBySearchDto(SearchInterface $searchDto): QueryBuilder
    {
        $sort = $searchDto->getSort() ?: $searchDto::SORT_DEFAULT;
        $sortDirection = $searchDto->getSortDirection() ?: $searchDto::SORT_DIRECTION_DEFAULT;

        $qb = $this->createQueryBuilder('s');

        if ($searchDto->getQuery()) {
            $qb->andWhere('s.name LIKE :query')
                ->setParameter('query', '%'.$searchDto->getQuery().'%');
        }

        if ($searchDto->getProductCode()) {
            $qb->andWhere('s.productCode = :productCode')
                ->setParameter('productCode', $searchDto->getProductCode());
        }

        if ($searchDto->getSupplierId()) {
            $qb->andWhere('s.supplier = :supplierId')
                ->setParameter('supplierId', $searchDto->getSupplierId());
        }

        if ($searchDto->getSupplierCategoryId()) {
            $qb->andWhere('s.supplierCategory = :supplierCategoryId')
                ->setParameter('supplierCategoryId', $searchDto->getSupplierCategoryId());
        }

        if ($searchDto->getSupplierSubcategoryId()) {
            $qb->andWhere('s.supplierSubcategory = :supplierSubcategoryId')
                ->setParameter('supplierSubcategoryId', $searchDto->getSupplierSubcategoryId());
        }

        if ($searchDto->getSupplierManufacturerId()) {
            $qb->andWhere('s.supplierManufacturer = :supplierManufacturerId')
                ->setParameter('supplierManufacturerId', $searchDto->getSupplierManufacturerId());
        }

        if ($searchDto->getInStock() !== null) {
            $qb->andWhere($searchDto->getInStock() > 0 ? 's.stock > 0' : 's.stock = 0');
        }

        if ($searchDto->getIsActive() !== null) {
            $qb->andWhere($searchDto->getIsActive() > 0 ? 's.isActive = 1' : 's.isActive = 0');
        }

        if ($sort) {
            if (str_starts_with($sort, 'supplier.')) {
                $qb->leftJoin('s.supplier', 'supplier')->orderBy($sort, $sortDirection);
            } else {
                $qb->orderBy('s.'.$sort, $sortDirection);
            }
        }

        return $qb;
    }

    public function findRandomSupplierProducts(Supplier $supplier, int $itemCount): array
    {
        return $this->createQueryBuilder('sp')
            ->where('sp.supplier = :supplier')
            ->setParameter('supplier', $supplier)
            ->setMaxResults($itemCount)
            ->addOrderBy('RAND()')
            ->getQuery()
            ->getResult();
    }
}
