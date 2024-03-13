<?php

namespace App\Repository;

use App\Entity\Product;
use App\Entity\ProductImage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
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
        $qb = $this->createQueryBuilder('pi');

        if ($query) {
            $qb->andWhere('pi.imageName LIKE :query')
                ->setParameter('query', '%' . $query . '%');
        }

        if ($sort) {
            if (str_starts_with($sort, 'product.')) {
                $qb->leftJoin('pi.product', 'product')->orderBy($sort, $direction);
            } else {
                $qb->orderBy('pi.' . $sort, $direction);
            }
        }

        return $qb;
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getNextPositionForProduct(Product $product): int
    {
        $query = $this->createQueryBuilder('pi')
            ->select('MAX(pi.position)')
            ->where('pi.product = :product')
            ->setParameter('product', $product)
            ->getQuery();

        $maxPosition = $query->getSingleScalarResult();

        return (int) $maxPosition + 1;
    }
}
