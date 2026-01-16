<?php

namespace App\Catalog\Infrastructure\Persistence\Doctrine;

use App\Catalog\Domain\Model\Category\Category;
use App\Catalog\Domain\Model\Category\CategoryId;
use App\Catalog\Domain\Model\Category\CategoryPublicId;
use App\Catalog\Domain\Repository\CategoryRepository;
use App\Shared\Application\Search\SearchCriteriaInterface;
use App\Shared\Infrastructure\Persistence\Search\FindByCriteriaInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Adapter\AdapterInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;

/**
 * @extends ServiceEntityRepository<Category>
 *
 * @method Category|null find($id, $lockMode = null, $lockVersion = null)
 * @method Category|null findOneBy(array $criteria, array $orderBy = null)
 * @method Category[]    findAll()
 * @method Category[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CategoryDoctrineRepository extends ServiceEntityRepository implements FindByCriteriaInterface, CategoryRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    public function add(Category $category): void
    {
        $this->getEntityManager()->persist($category);
    }

    public function remove(Category $category): void
    {
        $this->getEntityManager()->remove($category);
    }

    public function get(CategoryId $id): ?Category
    {
        return $this->find($id->value());
    }

    public function getByPublicId(CategoryPublicId $publicId): ?Category
    {
        return $this->findOneBy(['publicId' => $publicId->value()]);
    }

    public function findByCriteria(SearchCriteriaInterface $criteria): AdapterInterface
    {
        $sort = $criteria->getSort();
        $sortDirection = $criteria->getSortDirection();

        $qb = $this->createQueryBuilder('c');

        if ($criteria->getQuery()) {
            $qb->andWhere('c.name LIKE :query')
                ->setParameter('query', '%' . $criteria->getQuery() . '%');
        }

        if ($criteria->vatRateId) {
            $qb->andWhere('c.vatRate = :vatRateId')
                ->setParameter('vatRateId', $criteria->vatRateId);
        }

        if ($criteria->priceModel) {
            $qb->andWhere('c.priceModel = :priceModel')
                ->setParameter('priceModel', $criteria->priceModel);
        }

        if ($criteria->managerId) {
            $qb->andWhere('c.owner = :managerId')
                ->setParameter('managerId', $criteria->managerId);
        }

        $qb->orderBy('c.' . $sort, $sortDirection);

        return new QueryAdapter($qb);
    }

    public function findFromCategoryArray(array $categoryIds): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.id IN (:ids)')
            ->setParameter('ids', $categoryIds)
            ->getQuery()
            ->getResult();
    }
}
