<?php

namespace App\Note\Infrastructure\Persistence\Doctrine;

use App\Note\Application\Search\PoolSearchCriteria;
use App\Note\Domain\Model\Pool\Pool;
use App\Note\Domain\Model\Pool\PoolId;
use App\Note\Domain\Model\Pool\PoolPublicId;
use App\Note\Domain\Repository\PoolRepository;
use App\Shared\Application\Search\SearchCriteriaInterface;
use App\Shared\Infrastructure\Persistence\Search\FindByCriteriaInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Adapter\AdapterInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;

/**
 * @extends ServiceEntityRepository<Pool>
 *
 * @method Pool|null find($id, $lockMode = null, $lockVersion = null)
 * @method Pool|null findOneBy(array<string, mixed> $criteria, ?array<string, string> $orderBy = null)
 * @method Pool[]    findAll()
 * @method Pool[]    findBy(array<string, mixed> $criteria, ?array<string, string> $orderBy = null, $limit = null, $offset = null)
 */
class PoolDoctrineRepository extends ServiceEntityRepository implements FindByCriteriaInterface, PoolRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Pool::class);
    }

    public function add(Pool $pool): void
    {
        $this->getEntityManager()->persist($pool);
    }

    public function remove(Pool $pool): void
    {
        $this->getEntityManager()->remove($pool);
    }

    public function get(PoolId $id): ?Pool
    {
        return $this->find($id->value());
    }

    public function getByPublicId(PoolPublicId $publicId): ?Pool
    {
        return $this->findOneBy(['publicId' => $publicId->value()]);
    }

    /**
     * @return array<int, Pool>
     */
    public function findActive(): array
    {
        return $this->findBy(['isActive' => true], ['name' => 'ASC']);
    }

    /**
     * @return AdapterInterface<Pool>
     */
    public function findByCriteria(SearchCriteriaInterface $criteria): AdapterInterface
    {
        if (!$criteria instanceof PoolSearchCriteria) {
            throw new \InvalidArgumentException('Expected PoolSearchCriteria');
        }

        $sort = $criteria->getSort();
        $sortDirection = $criteria->getSortDirection();

        $qb = $this->createQueryBuilder('p');

        if ($criteria->getQuery()) {
            $qb->andWhere('p.name LIKE :query')
                ->setParameter('query', '%' . $criteria->getQuery() . '%');
        }

        $qb->orderBy('p.' . $sort, $sortDirection);

        return new QueryAdapter($qb);
    }
}
