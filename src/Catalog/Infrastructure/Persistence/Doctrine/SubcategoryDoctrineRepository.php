<?php

namespace App\Catalog\Infrastructure\Persistence\Doctrine;

use App\Catalog\Domain\Model\Subcategory\Subcategory;
use App\Catalog\Domain\Model\Subcategory\SubcategoryId;
use App\Catalog\Domain\Model\Subcategory\SubcategoryPublicId;
use App\Catalog\Domain\Repository\SubcategoryRepository;
use App\Shared\Application\Search\SearchCriteriaInterface;
use App\Shared\Infrastructure\Persistence\Search\FindByCriteriaInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Adapter\AdapterInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;

/**
 * @extends ServiceEntityRepository<Subcategory>
 *
 * @method Subcategory|null find($id, $lockMode = null, $lockVersion = null)
 * @method Subcategory|null findOneBy(array $criteria, array $orderBy = null)
 * @method Subcategory[]    findAll()
 * @method Subcategory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SubcategoryDoctrineRepository extends ServiceEntityRepository implements FindByCriteriaInterface, SubcategoryRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Subcategory::class);
    }

    public function add(Subcategory $subcategory): void
    {
        $this->getEntityManager()->persist($subcategory);
    }

    public function remove(Subcategory $subcategory): void
    {
        $this->getEntityManager()->remove($subcategory);
    }

    public function get(SubcategoryId $id): ?Subcategory
    {
        return $this->find($id->value());
    }

    public function getByPublicId(SubcategoryPublicId $publicId): ?Subcategory
    {
        return $this->findOneBy(['publicId' => $publicId->value()]);
    }

    public function findByCriteria(SearchCriteriaInterface $criteria): AdapterInterface
    {
        $sort = $criteria->getSort();
        $sortDirection = $criteria->getSortDirection();

        $qb = $this->createQueryBuilder('s');

        if ($criteria->getQuery()) {
            $qb->andWhere('s.name LIKE :query')
                ->setParameter('query', '%' . $criteria->getQuery() . '%');
        }

        if ($criteria->categoryId) {
            $qb->andWhere('s.category = :categoryId')
                ->setParameter('categoryId', $criteria->categoryId);
        }

        if ($criteria->priceModel) {
            $qb->andWhere('s.priceModel = :priceModel')
                ->setParameter('priceModel', $criteria->priceModel);
        }

        if ($criteria->managerId) {
            $qb->andWhere('s.owner = :managerId')
                ->setParameter('managerId', $criteria->managerId);
        }

        if ($sort !== '' && $sort !== '0') {
            if (str_starts_with($sort, 'category.')) {
                $qb->leftJoin('s.category', 'category')->orderBy($sort, $sortDirection);
            } else {
                $qb->orderBy('s.' . $sort, $sortDirection);
            }
        }

        return new QueryAdapter($qb);
    }
}
