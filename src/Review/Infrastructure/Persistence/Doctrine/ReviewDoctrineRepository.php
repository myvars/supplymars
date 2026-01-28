<?php

namespace App\Review\Infrastructure\Persistence\Doctrine;

use App\Catalog\Domain\Model\Product\Product;
use App\Customer\Domain\Model\User\User;
use App\Review\Application\Search\ReviewSearchCriteria;
use App\Review\Domain\Model\Review\ProductReview;
use App\Review\Domain\Model\Review\ReviewPublicId;
use App\Review\Domain\Model\Review\ReviewStatus;
use App\Review\Domain\Repository\ReviewRepository;
use App\Shared\Application\Search\SearchCriteriaInterface;
use App\Shared\Infrastructure\Persistence\Search\FindByCriteriaInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ParameterType;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Adapter\AdapterInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;

/**
 * @extends ServiceEntityRepository<ProductReview>
 *
 * @method ProductReview|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProductReview|null findOneBy(array<string, mixed> $criteria, ?array<string, string> $orderBy = null)
 * @method ProductReview[]    findAll()
 * @method ProductReview[]    findBy(array<string, mixed> $criteria, ?array<string, string> $orderBy = null, $limit = null, $offset = null)
 */
class ReviewDoctrineRepository extends ServiceEntityRepository implements FindByCriteriaInterface, ReviewRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductReview::class);
    }

    public function add(ProductReview $review): void
    {
        $this->getEntityManager()->persist($review);
    }

    public function remove(ProductReview $review): void
    {
        $this->getEntityManager()->remove($review);
    }

    public function getByPublicId(ReviewPublicId $publicId): ?ProductReview
    {
        return $this->findOneBy(['publicId' => $publicId->value()]);
    }

    public function findByCustomerAndProduct(User $customer, Product $product): ?ProductReview
    {
        return $this->findOneBy([
            'customer' => $customer,
            'product' => $product,
        ]);
    }

    /** @return ProductReview[] */
    public function findLatestPublishedForProduct(Product $product, int $limit = 5): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.product = :product')
            ->andWhere('r.status = :status')
            ->setParameter('product', $product)
            ->setParameter('status', ReviewStatus::PUBLISHED)
            ->orderBy('r.publishedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return AdapterInterface<ProductReview>
     */
    public function findByCriteria(SearchCriteriaInterface $criteria): AdapterInterface
    {
        if (!$criteria instanceof ReviewSearchCriteria) {
            throw new \InvalidArgumentException('Expected ReviewSearchCriteria');
        }

        $sort = $criteria->getSort();
        $sortDirection = $criteria->getSortDirection();

        $qb = $this->createQueryBuilder('r');

        if ($criteria->getQuery()) {
            $qb->andWhere('r.id LIKE :query OR r.title LIKE :query')
                ->setParameter('query', '%' . $criteria->getQuery() . '%');
        }

        if ($criteria->reviewStatus) {
            $qb->andWhere('r.status = :status')
                ->setParameter('status', $criteria->reviewStatus);
        }

        if ($criteria->productId) {
            $qb->andWhere('r.product = :productId')
                ->setParameter('productId', $criteria->productId);
        }

        if ($criteria->customerId) {
            $qb->andWhere('r.customer = :customerId')
                ->setParameter('customerId', $criteria->customerId);
        }

        if ($criteria->rating) {
            $qb->andWhere('r.rating = :rating')
                ->setParameter('rating', $criteria->rating);
        }

        $qb->orderBy('r.' . $sort, $sortDirection);

        return new QueryAdapter($qb);
    }

    /**
     * @return array{count: int, average: string, distribution: array<int, int>, pendingCount: int}
     */
    public function getProductReviewStats(Product $product): array
    {
        $publishedStats = $this->createQueryBuilder('r')
            ->select('COUNT(r.id) as reviewCount, COALESCE(AVG(r.rating), 0) as averageRating')
            ->andWhere('r.product = :product')
            ->andWhere('r.status = :status')
            ->setParameter('product', $product)
            ->setParameter('status', ReviewStatus::PUBLISHED)
            ->getQuery()
            ->getSingleResult();

        $distribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        $ratingRows = $this->createQueryBuilder('r')
            ->select('r.rating, COUNT(r.id) as cnt')
            ->andWhere('r.product = :product')
            ->andWhere('r.status = :status')
            ->setParameter('product', $product)
            ->setParameter('status', ReviewStatus::PUBLISHED)
            ->groupBy('r.rating')
            ->getQuery()
            ->getResult();

        foreach ($ratingRows as $row) {
            $distribution[(int) $row['rating']] = (int) $row['cnt'];
        }

        $pendingCount = $this->count([
            'product' => $product,
            'status' => ReviewStatus::PENDING,
        ]);

        return [
            'count' => (int) $publishedStats['reviewCount'],
            'average' => number_format((float) $publishedStats['averageRating'], 2),
            'distribution' => $distribution,
            'pendingCount' => $pendingCount,
        ];
    }

    /**
     * Find eligible orders that don't have reviews yet.
     *
     * @return array<int, array{customer_id: int, product_id: int, order_id: int}>
     */
    public function findEligibleOrderIds(int $limit, ?int $productId = null): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
            SELECT DISTINCT oi.product_id, co.id AS order_id, co.customer_id
            FROM customer_order_item oi
            INNER JOIN customer_order co ON co.id = oi.customer_order_id
            LEFT JOIN product_review pr ON pr.customer_id = co.customer_id AND pr.product_id = oi.product_id
            WHERE co.status = :deliveredStatus
            AND pr.id IS NULL
        ';

        $params = ['deliveredStatus' => 'DELIVERED'];

        if ($productId !== null) {
            $sql .= ' AND oi.product_id = :productId';
            $params['productId'] = $productId;
        }

        $sql .= ' ORDER BY RAND() LIMIT :limit';
        $params['limit'] = $limit;

        /** @var array<int, array{customer_id: int, product_id: int, order_id: int}> $rows */
        $rows = $conn->fetchAllAssociative($sql, $params, ['limit' => ParameterType::INTEGER]);

        return $rows;
    }
}
