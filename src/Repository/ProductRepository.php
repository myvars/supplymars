<?php

namespace App\Repository;

use App\DTO\SearchDto\SearchInterface;
use App\Entity\Product;
use App\Enum\PurchaseOrderStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 *
 * @method Product|null find($id, $lockMode = null, $lockVersion = null)
 * @method Product|null findOneBy(array $criteria, array $orderBy = null)
 * @method Product[]    findAll()
 * @method Product[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductRepository extends ServiceEntityRepository implements SearchQueryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function findBySearchDto(SearchInterface $searchDto): QueryBuilder
    {
        $sort = $searchDto->getSort() ?: $searchDto::SORT_DEFAULT;
        $sortDirection = $searchDto->getSortDirection() ?: $searchDto::SORT_DIRECTION_DEFAULT;

        $qb = $this->createQueryBuilder('p');

        if ($searchDto->getQuery()) {
            $qb->andWhere('p.name LIKE :query')
                ->setParameter('query', '%'.$searchDto->getQuery().'%');
        }

        if ($searchDto->getMfrPartNumber()) {
            $qb->andWhere('p.mfrPartNumber = :mfrPartNumber')
                ->setParameter('mfrPartNumber', $searchDto->getMfrPartNumber());
        }

        if ($searchDto->getCategoryId()) {
            $qb->andWhere('p.category = :categoryId')
                ->setParameter('categoryId', $searchDto->getCategoryId());
        }

        if ($searchDto->getSubcategoryId()) {
            $qb->andWhere('p.subcategory = :subcategoryId')
                ->setParameter('subcategoryId', $searchDto->getSubcategoryId());
        }

        if ($searchDto->getManufacturerId()) {
            $qb->andWhere('p.manufacturer = :manufacturerId')
                ->setParameter('manufacturerId', $searchDto->getManufacturerId());
        }

        if ($searchDto->getInStock() !== null) {
            $qb->andWhere($searchDto->getInStock() > 0 ? 'p.stock > 0' : 'p.stock = 0');
        }

        $qb->orderBy('p.'.$sort, $sortDirection);

        return $qb;
    }

    public function findRandomProducts(int $limit = 10): array
    {
        //change the query to join on active product source
        return $this->getEntityManager()->createQuery('
            SELECT p FROM App\Entity\Product p
            JOIN p.supplierProducts sp
            WHERE p.isActive = true AND p.stock > 0 AND sp.isActive = true
            ORDER BY RAND()
            ')
            ->setMaxResults($limit)
            ->getResult();

    }
}
