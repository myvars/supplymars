<?php

namespace App\Repository;

use App\DTO\SearchDto\SearchInterface;
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
class SubcategoryRepository extends ServiceEntityRepository implements SearchQueryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Subcategory::class);
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

        if ($searchDto->getCategoryId()) {
            $qb->andWhere('s.category = :categoryId')
                ->setParameter('categoryId', $searchDto->getCategoryId());
        }

        if ($sort) {
            if (str_starts_with($sort, 'category.')) {
                $qb->leftJoin('s.category', 'category')->orderBy($sort, $sortDirection);
            } else {
                $qb->orderBy('s.'.$sort, $sortDirection);
            }
        }

        return $qb;
    }
}
