<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
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
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * @throws NonUniqueResultException
     */
/*    public function findFullProduct(?int $id = null): ?Product
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->leftJoin('p.subcategory', 's')
            ->leftJoin('p.manufacturer', 'm')
            ->leftJoin('p.vatRate', 'v')
            ->select('p', 'c', 's', 'm', 'v')
            ->andWhere('p.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }*/

    public function findBySearch(?string $query, ?int $limit = null): array
    {
        $qb = $this->findBySearchQueryBuilder($query);

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb
            ->getQuery()
            ->getResult();
    }

    public function findBySearchQueryBuilder(?string $query, ?string $sort = null, string $direction = 'DESC'): QueryBuilder
    {
        $qb = $this->createQueryBuilder('p');

        if ($query) {
            $qb->andWhere('p.name LIKE :query')
                ->setParameter('query', '%'.$query.'%');
        }

        if ($sort) {
            $qb->orderBy('p.'.$sort, $direction);
        }

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
