<?php

namespace App\Catalog\Infrastructure\Persistence\Doctrine;

use App\Catalog\Application\Search\ProductSearchCriteria;
use App\Catalog\Domain\Model\Product\Product;
use App\Catalog\Domain\Model\Product\ProductId;
use App\Catalog\Domain\Model\Product\ProductPublicId;
use App\Catalog\Domain\Repository\ProductRepository;
use App\Shared\Application\Search\SearchCriteriaInterface;
use App\Shared\Infrastructure\Persistence\Search\FindByCriteriaInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Adapter\AdapterInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;

/**
 * @extends ServiceEntityRepository<Product>
 *
 * @method Product|null find($id, $lockMode = null, $lockVersion = null)
 * @method Product|null findOneBy(array<string, mixed> $criteria, ?array<string, string> $orderBy = null)
 * @method Product[]    findAll()
 * @method Product[]    findBy(array<string, mixed> $criteria, ?array<string, string> $orderBy = null, $limit = null, $offset = null)
 */
class ProductDoctrineRepository extends ServiceEntityRepository implements FindByCriteriaInterface, ProductRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function add(Product $product): void
    {
        $this->getEntityManager()->persist($product);
    }

    public function remove(Product $product): void
    {
        $this->getEntityManager()->remove($product);
    }

    public function get(ProductId $id): ?Product
    {
        return $this->find($id->value());
    }

    public function getByPublicId(ProductPublicId $publicId): ?Product
    {
        return $this->findOneBy(['publicId' => $publicId->value()]);
    }

    /**
     * @return AdapterInterface<Product>
     */
    public function findByCriteria(SearchCriteriaInterface $criteria): AdapterInterface
    {
        if (!$criteria instanceof ProductSearchCriteria) {
            throw new \InvalidArgumentException('Expected ProductSearchCriteria');
        }

        $sort = $criteria->getSort();
        $sortDirection = $criteria->getSortDirection();

        $qb = $this->createQueryBuilder('p');

        if ($criteria->getQuery()) {
            $qb->andWhere('p.name LIKE :query')
                ->setParameter('query', '%' . $criteria->getQuery() . '%');
        }

        if ($criteria->mfrPartNumber) {
            $qb->andWhere('p.mfrPartNumber = :mfrPartNumber')
                ->setParameter('mfrPartNumber', $criteria->mfrPartNumber);
        }

        if ($criteria->categoryId) {
            $qb->andWhere('p.category = :categoryId')
                ->setParameter('categoryId', $criteria->categoryId);
        }

        if ($criteria->subcategoryId) {
            $qb->andWhere('p.subcategory = :subcategoryId')
                ->setParameter('subcategoryId', $criteria->subcategoryId);
        }

        if ($criteria->manufacturerId) {
            $qb->andWhere('p.manufacturer = :manufacturerId')
                ->setParameter('manufacturerId', $criteria->manufacturerId);
        }

        if (null !== $criteria->inStock) {
            $qb->andWhere($criteria->inStock > 0 ? 'p.stock > 0' : 'p.stock = 0');
        }

        $qb->orderBy('p.' . $sort, $sortDirection);

        return new QueryAdapter($qb);
    }

    /**
     * @return array<int, Product>
     */
    public function findRandomProducts(int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->select('DISTINCT p', 'rand() AS HIDDEN rnd')
            ->innerJoin('p.supplierProducts', 'sp', 'WITH', 'sp.isActive = :active')
            ->andWhere('p.isActive = :active')
            ->andWhere('p.stock > 0')
            ->setParameter('active', true)
            ->orderBy('rnd')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param array<int, int> $productIds
     *
     * @return array<int, Product>
     */
    public function findFromProductArray(array $productIds): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.id IN (:ids)')
            ->setParameter('ids', $productIds)
            ->getQuery()
            ->getResult();
    }

    public function findByMfrPartNumber(string $mfrPartNumber): ?Product
    {
        return $this->findOneBy(['mfrPartNumber' => $mfrPartNumber]);
    }
}
