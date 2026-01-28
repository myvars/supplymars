<?php

namespace App\Review\Infrastructure\Persistence\Doctrine;

use App\Catalog\Domain\Model\Product\Product;
use App\Review\Domain\Model\ReviewSummary\ProductReviewSummary;
use App\Review\Domain\Repository\ReviewSummaryRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProductReviewSummary>
 *
 * @method ProductReviewSummary|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProductReviewSummary|null findOneBy(array<string, mixed> $criteria, ?array<string, string> $orderBy = null)
 * @method ProductReviewSummary[]    findAll()
 * @method ProductReviewSummary[]    findBy(array<string, mixed> $criteria, ?array<string, string> $orderBy = null, $limit = null, $offset = null)
 */
class ReviewSummaryDoctrineRepository extends ServiceEntityRepository implements ReviewSummaryRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductReviewSummary::class);
    }

    public function add(ProductReviewSummary $summary): void
    {
        $this->getEntityManager()->persist($summary);
    }

    public function findByProduct(Product $product): ?ProductReviewSummary
    {
        return $this->findOneBy(['product' => $product]);
    }
}
