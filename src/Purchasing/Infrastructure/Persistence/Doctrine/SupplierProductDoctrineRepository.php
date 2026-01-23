<?php

namespace App\Purchasing\Infrastructure\Persistence\Doctrine;

use App\Purchasing\Application\Search\SupplierProductSearchCriteria;
use App\Purchasing\Domain\Model\Supplier\Supplier;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProduct;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProductId;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProductPublicId;
use App\Purchasing\Domain\Repository\SupplierProductRepository;
use App\Shared\Application\Search\SearchCriteriaInterface;
use App\Shared\Infrastructure\Persistence\Search\FindByCriteriaInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Adapter\AdapterInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;

/**
 * @extends ServiceEntityRepository<SupplierProduct>
 *
 * @method SupplierProduct|null find($id, $lockMode = null, $lockVersion = null)
 * @method SupplierProduct|null findOneBy(array<string, mixed> $criteria, ?array<string, string> $orderBy = null)
 * @method SupplierProduct[]    findAll()
 * @method SupplierProduct[]    findBy(array<string, mixed> $criteria, ?array<string, string> $orderBy = null, $limit = null, $offset = null)
 */
class SupplierProductDoctrineRepository extends ServiceEntityRepository implements FindByCriteriaInterface, SupplierProductRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SupplierProduct::class);
    }

    public function add(SupplierProduct $supplierProduct): void
    {
        $this->getEntityManager()->persist($supplierProduct);
    }

    public function remove(SupplierProduct $supplierProduct): void
    {
        $this->getEntityManager()->remove($supplierProduct);
    }

    public function get(SupplierProductId $id): ?SupplierProduct
    {
        return $this->find($id->value());
    }

    public function getByPublicId(SupplierProductPublicId $publicId): ?SupplierProduct
    {
        return $this->findOneBy(['publicId' => $publicId->value()]);
    }

    /**
     * @return AdapterInterface<SupplierProduct>
     */
    public function findByCriteria(SearchCriteriaInterface $criteria): AdapterInterface
    {
        if (!$criteria instanceof SupplierProductSearchCriteria) {
            throw new \InvalidArgumentException('Expected SupplierProductSearchCriteria');
        }

        $sort = $criteria->getSort();
        $sortDirection = $criteria->getSortDirection();

        $qb = $this->createQueryBuilder('s');

        if ($criteria->getQuery()) {
            $qb->andWhere('s.name LIKE :query')
                ->setParameter('query', '%' . $criteria->getQuery() . '%');
        }

        if ($criteria->productCode) {
            $qb->andWhere('s.productCode = :productCode')
                ->setParameter('productCode', $criteria->productCode);
        }

        if ($criteria->supplierId) {
            $qb->andWhere('s.supplier = :supplierId')
                ->setParameter('supplierId', $criteria->supplierId);
        }

        if ($criteria->supplierCategoryId) {
            $qb->andWhere('s.supplierCategory = :supplierCategoryId')
                ->setParameter('supplierCategoryId', $criteria->supplierCategoryId);
        }

        if ($criteria->supplierSubcategoryId) {
            $qb->andWhere('s.supplierSubcategory = :supplierSubcategoryId')
                ->setParameter('supplierSubcategoryId', $criteria->supplierSubcategoryId);
        }

        if ($criteria->supplierManufacturerId) {
            $qb->andWhere('s.supplierManufacturer = :supplierManufacturerId')
                ->setParameter('supplierManufacturerId', $criteria->supplierManufacturerId);
        }

        if (null !== $criteria->inStock) {
            $qb->andWhere($criteria->inStock > 0 ? 's.stock > 0' : 's.stock = 0');
        }

        if (null !== $criteria->isActive) {
            $qb->andWhere($criteria->isActive > 0 ? 's.isActive = 1' : 's.isActive = 0');
        }

        if ($sort !== '' && $sort !== '0') {
            if (str_starts_with($sort, 'supplier.')) {
                $qb->leftJoin('s.supplier', 'supplier')->orderBy($sort, $sortDirection);
            } else {
                $qb->orderBy('s.' . $sort, $sortDirection);
            }
        }

        return new QueryAdapter($qb);
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

    public function findBySupplier(Supplier $supplier): array
    {
        return $this->findBy(['supplier' => $supplier]);
    }

    public function findInactive(int $limit): array
    {
        return $this->findBy(['isActive' => false], null, $limit);
    }
}
