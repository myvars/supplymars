<?php

namespace App\Repository;

use App\Entity\PriceModel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PriceModel>
 *
 * @method PriceModel|null find($id, $lockMode = null, $lockVersion = null)
 * @method PriceModel|null findOneBy(array $criteria, array $orderBy = null)
 * @method PriceModel[]    findAll()
 * @method PriceModel[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PriceModelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PriceModel::class);
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
        $qb = $this->createQueryBuilder('v');

        if ($query) {
            $qb->andWhere('v.name LIKE :query OR v.description LIKE :query OR v.modelTag LIKE :query')
                ->setParameter('query', '%' . $query . '%');
        }

        if ($sort) {
            $qb->orderBy('v.' . $sort, $direction);
        }

        return $qb;
    }
}