<?php

namespace App\Repository;

use App\Entity\Subcategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Subcategory>
 *
 * @method Subcategory|null find($id, $lockMode = null, $lockVersion = null)
 * @method Subcategory|null findOneBy(array $criteria, array $orderBy = null)
 * @method Subcategory[]    findAll()
 * @method Subcategory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SubcategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Subcategory::class);
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
        $qb = $this->createQueryBuilder('s');

        if ($query) {
            $qb->andWhere('s.name LIKE :query')
                ->setParameter('query', '%' . $query . '%');
        }

        if ($sort) {

            if (str_starts_with($sort, 'category.')) {
                $qb->leftJoin('s.category', 'category')->orderBy($sort, $direction);
            } else {
                $qb->orderBy('s.' . $sort, $direction);
            }

        }

        return $qb;
    }
}
