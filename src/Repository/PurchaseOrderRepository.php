<?php

namespace App\Repository;

use App\Entity\PurchaseOrder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PurchaseOrder>
 *
 * @method PurchaseOrder|null find($id, $lockMode = null, $lockVersion = null)
 * @method PurchaseOrder|null findOneBy(array $criteria, array $orderBy = null)
 * @method PurchaseOrder[]    findAll()
 * @method PurchaseOrder[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PurchaseOrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PurchaseOrder::class);
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
        $qb = $this->createQueryBuilder('p');

        if ($query) {
            $qb->andWhere('p.id LIKE :query')
                ->setParameter('query', '%'.$query.'%');
        }

        if ($sort) {
            if (str_starts_with($sort, 'customerOrder.')) {
                $qb->leftJoin('p.customerOrder', 'customerOrder')->orderBy($sort, $direction);
            } else {
                $qb->orderBy('p.'.$sort, $direction);
            }
        }

        return $qb;
    }
}
