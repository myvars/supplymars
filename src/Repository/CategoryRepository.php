<?php

namespace App\Repository;

use App\DTO\SearchDto\SearchInterface;
use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 *
 * @method Category|null find($id, $lockMode = null, $lockVersion = null)
 * @method Category|null findOneBy(array $criteria, array $orderBy = null)
 * @method Category[]    findAll()
 * @method Category[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CategoryRepository extends ServiceEntityRepository implements SearchQueryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    public function findBySearchDto(SearchInterface $searchDto): QueryBuilder
    {
        $sort = $searchDto->getSort() ?: $searchDto::SORT_DEFAULT;
        $sortDirection = $searchDto->getSortDirection() ?: $searchDto::SORT_DIRECTION_DEFAULT;

        $qb = $this->createQueryBuilder('c');

        if ($searchDto->getQuery()) {
            $qb->andWhere('c.name LIKE :query')
                ->setParameter('query', '%'.$searchDto->getQuery().'%');
        }

        if ($searchDto->getPriceModel()) {
            $qb->andWhere('c.priceModel = :priceModel')
                ->setParameter('priceModel', $searchDto->getPriceModel());
        }

        if ($searchDto->getManagerId()) {
            $qb->andWhere('c.owner = :managerId')
                ->setParameter('managerId', $searchDto->getManagerId());
        }

        $qb->orderBy('c.'.$sort, $sortDirection);

        return $qb;
    }
}
