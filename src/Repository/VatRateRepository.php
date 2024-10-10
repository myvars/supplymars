<?php

namespace App\Repository;

use App\DTO\SearchDto\SearchInterface;
use App\Entity\VatRate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<VatRate>
 *
 * @method VatRate|null find($id, $lockMode = null, $lockVersion = null)
 * @method VatRate|null findOneBy(array $criteria, array $orderBy = null)
 * @method VatRate[]    findAll()
 * @method VatRate[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VatRateRepository extends ServiceEntityRepository implements SearchQueryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VatRate::class);
    }

    public function findDefaultVatRate(): ?VatRate
    {
        return $this->findOneBy(['isDefaultVatRate' => true]);
    }

    public function findBySearchDto(SearchInterface $searchDto): QueryBuilder
    {
        $sort = $searchDto->getSort() ?: $searchDto::SORT_DEFAULT;
        $sortDirection = $searchDto->getSortDirection() ?: $searchDto::SORT_DIRECTION_DEFAULT;

        $qb = $this->createQueryBuilder('v');

        if ($searchDto->getQuery()) {
            $qb->andWhere('v.name LIKE :query')
                ->setParameter('query', '%'.$searchDto->getQuery().'%');
        }

        $qb->orderBy('v.'.$sort, $sortDirection);

        return $qb;
    }
}
