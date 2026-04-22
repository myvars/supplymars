<?php

declare(strict_types=1);

namespace App\Review\Domain\Repository;

use App\Catalog\Domain\Model\Product\Product;
use App\Customer\Domain\Model\User\User;
use App\Review\Domain\Model\Review\ProductReview;
use App\Review\Domain\Model\Review\ReviewPublicId;
use App\Review\Infrastructure\Persistence\Doctrine\ReviewDoctrineRepository;
use App\Shared\Infrastructure\Persistence\Search\FindByCriteriaInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(ReviewDoctrineRepository::class)]
interface ReviewRepository extends FindByCriteriaInterface
{
    public function add(ProductReview $review): void;

    public function remove(ProductReview $review): void;

    public function getByPublicId(ReviewPublicId $publicId): ?ProductReview;

    public function findByCustomerAndProduct(User $customer, Product $product): ?ProductReview;

    /** @return ProductReview[] */
    public function findLatestPublishedForProduct(Product $product, int $limit = 5): array;

    public function countPendingReviews(): int;
}
