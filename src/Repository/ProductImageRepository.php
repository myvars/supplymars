<?php

namespace App\Repository;

use App\Entity\ProductImage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProductImage>
 *
 * @method ProductImage|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProductImage|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProductImage[]    findAll()
 * @method ProductImage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductImageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductImage::class);
    }

    public function findBySearch(?string $query, int $limit = null): array
    {
        $qb =  $this->findBySearchQueryBuilder($query);

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
                ->setParameter('query', '%' . $query . '%');
        }

        if ($sort) {
            $qb->orderBy('p.' . $sort, $direction);
        }

        return $qb;
    }
}
