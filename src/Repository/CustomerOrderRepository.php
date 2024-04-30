<?php

namespace App\Repository;

use App\Entity\CustomerOrder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CustomerOrder>
 *
 * @method CustomerOrder|null find($id, $lockMode = null, $lockVersion = null)
 * @method CustomerOrder|null findOneBy(array $criteria, array $orderBy = null)
 * @method CustomerOrder[]    findAll()
 * @method CustomerOrder[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CustomerOrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CustomerOrder::class);
    }

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
        $qb = $this->createQueryBuilder('o');

        if ($query) {
            $qb->andWhere('o.name LIKE :query')
                ->setParameter('query', '%'.$query.'%');
        }

        if ($sort) {
            if (str_starts_with($sort, 'customer.')) {
                $qb->leftJoin('o.customer', 'customer')->orderBy($sort, $direction);
            } else {
                $qb->orderBy('o.'.$sort, $direction);
            }
        }

        return $qb;
    }
}
