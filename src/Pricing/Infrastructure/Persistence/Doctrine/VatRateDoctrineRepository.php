<?php

namespace App\Pricing\Infrastructure\Persistence\Doctrine;

use App\Pricing\Domain\Model\VatRate\VatRate;
use App\Pricing\Domain\Model\VatRate\VatRateId;
use App\Pricing\Domain\Model\VatRate\VatRatePublicId;
use App\Pricing\Domain\Repository\VatRateRepository;
use App\Shared\Application\Search\SearchCriteriaInterface;
use App\Shared\Infrastructure\Persistence\Search\FindByCriteriaInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Adapter\AdapterInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;

/**
 * @extends ServiceEntityRepository<VatRate>
 *
 * @method VatRate|null find($id, $lockMode = null, $lockVersion = null)
 * @method VatRate|null findOneBy(array $criteria, array $orderBy = null)
 * @method VatRate[]    findAll()
 * @method VatRate[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VatRateDoctrineRepository extends ServiceEntityRepository implements FindByCriteriaInterface, VatRateRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VatRate::class);
    }

    public function add(VatRate $vatRate): void
    {
        $this->getEntityManager()->persist($vatRate);
    }

    public function remove(VatRate $vatRate): void
    {
        $this->getEntityManager()->remove($vatRate);
    }

    public function get(VatRateId $id): ?VatRate
    {
        return $this->find($id->value());
    }

    public function getByPublicId(VatRatePublicId $publicId): ?VatRate
    {
        return $this->findOneBy(['publicId' => $publicId->value()]);
    }

    public function findDefaultVatRate(): ?VatRate
    {
        return $this->findOneBy(['isDefaultVatRate' => true]);
    }

    public function findByCriteria(SearchCriteriaInterface $criteria): AdapterInterface
    {
        $sort = $criteria->getSort();
        $sortDirection = $criteria->getSortDirection();

        $qb = $this->createQueryBuilder('v');

        if ($criteria->getQuery()) {
            $qb->andWhere('v.name LIKE :query')
                ->setParameter('query', '%' . $criteria->getQuery() . '%');
        }

        $qb->orderBy('v.' . $sort, $sortDirection);

        return new QueryAdapter($qb);
    }
}
