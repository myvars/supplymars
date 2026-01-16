<?php

namespace App\Catalog\Infrastructure\Persistence\Doctrine;

use App\Catalog\Domain\Model\Manufacturer\Manufacturer;
use App\Catalog\Domain\Model\Manufacturer\ManufacturerId;
use App\Catalog\Domain\Model\Manufacturer\ManufacturerPublicId;
use App\Catalog\Domain\Repository\ManufacturerRepository;
use App\Shared\Application\Search\SearchCriteriaInterface;
use App\Shared\Infrastructure\Persistence\Search\FindByCriteriaInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Adapter\AdapterInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;

/**
 * @extends ServiceEntityRepository<Manufacturer>
 *
 * @method Manufacturer|null find($id, $lockMode = null, $lockVersion = null)
 * @method Manufacturer|null findOneBy(array $criteria, array $orderBy = null)
 * @method Manufacturer[]    findAll()
 * @method Manufacturer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ManufacturerDoctrineRepository extends ServiceEntityRepository implements FindByCriteriaInterface, ManufacturerRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Manufacturer::class);
    }

    public function add(Manufacturer $manufacturer): void
    {
        $this->getEntityManager()->persist($manufacturer);
    }

    public function remove(Manufacturer $manufacturer): void
    {
        $this->getEntityManager()->remove($manufacturer);
    }

    public function get(ManufacturerId $id): ?Manufacturer
    {
        return $this->find($id->value());
    }

    public function getByPublicId(ManufacturerPublicId $publicId): ?Manufacturer
    {
        return $this->findOneBy(['publicId' => $publicId->value()]);
    }

    public function findByCriteria(SearchCriteriaInterface $criteria): AdapterInterface
    {
        $sort = $criteria->getSort();
        $sortDirection = $criteria->getSortDirection();

        $qb = $this->createQueryBuilder('m');

        if ($criteria->getQuery()) {
            $qb->andWhere('m.name LIKE :query')
                ->setParameter('query', '%' . $criteria->getQuery() . '%');
        }

        $qb->orderBy('m.' . $sort, $sortDirection);

        return new QueryAdapter($qb);
    }
}
