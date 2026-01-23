<?php

namespace App\Purchasing\Infrastructure\Persistence\Doctrine;

use App\Purchasing\Application\Search\SupplierSearchCriteria;
use App\Purchasing\Domain\Model\Supplier\Supplier;
use App\Purchasing\Domain\Model\Supplier\SupplierId;
use App\Purchasing\Domain\Model\Supplier\SupplierPublicId;
use App\Purchasing\Domain\Repository\SupplierRepository;
use App\Shared\Application\Search\SearchCriteriaInterface;
use App\Shared\Infrastructure\Persistence\Search\FindByCriteriaInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Adapter\AdapterInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;

/**
 * @extends ServiceEntityRepository<Supplier>
 *
 * @method Supplier|null find($id, $lockMode = null, $lockVersion = null)
 * @method Supplier|null findOneBy(array<string, mixed> $criteria, ?array<string, string> $orderBy = null)
 * @method Supplier[]    findAll()
 * @method Supplier[]    findBy(array<string, mixed> $criteria, ?array<string, string> $orderBy = null, $limit = null, $offset = null)
 */
class SupplierDoctrineRepository extends ServiceEntityRepository implements FindByCriteriaInterface, SupplierRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Supplier::class);
    }

    public function add(Supplier $supplier): void
    {
        $this->getEntityManager()->persist($supplier);
    }

    public function remove(Supplier $supplier): void
    {
        $this->getEntityManager()->remove($supplier);
    }

    public function get(SupplierId $id): ?Supplier
    {
        return $this->find($id->value());
    }

    public function getByPublicId(SupplierPublicId $publicId): ?Supplier
    {
        return $this->findOneBy(['publicId' => $publicId->value()]);
    }

    public function getRandomSupplier(): ?Supplier
    {
        $suppliers = $this->findAll();

        return $suppliers[array_rand($suppliers)] ?? null;
    }

    public function getWarehouseSupplier(): ?Supplier
    {
        return $this->findOneBy(['isWarehouse' => true]);
    }

    public function findNonWarehouseSupplier(): array
    {
        return $this->findBy(['isWarehouse' => false]);
    }

    /**
     * @return AdapterInterface<Supplier>
     */
    public function findByCriteria(SearchCriteriaInterface $criteria): AdapterInterface
    {
        if (!$criteria instanceof SupplierSearchCriteria) {
            throw new \InvalidArgumentException('Expected SupplierSearchCriteria');
        }

        $sort = $criteria->getSort();
        $sortDirection = $criteria->getSortDirection();

        $qb = $this->createQueryBuilder('s');

        if ($criteria->getQuery()) {
            $qb->andWhere('s.name LIKE :query')
                ->setParameter('query', '%' . $criteria->getQuery() . '%');
        }

        $qb->orderBy('s.' . $sort, $sortDirection);

        return new QueryAdapter($qb);
    }
}
