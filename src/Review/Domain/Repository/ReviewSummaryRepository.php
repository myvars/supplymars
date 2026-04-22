<?php

declare(strict_types=1);

namespace App\Review\Domain\Repository;

use App\Catalog\Domain\Model\Product\Product;
use App\Review\Domain\Model\ReviewSummary\ProductReviewSummary;
use App\Review\Infrastructure\Persistence\Doctrine\ReviewSummaryDoctrineRepository;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(ReviewSummaryDoctrineRepository::class)]
interface ReviewSummaryRepository
{
    public function add(ProductReviewSummary $summary): void;

    public function findByProduct(Product $product): ?ProductReviewSummary;
}
