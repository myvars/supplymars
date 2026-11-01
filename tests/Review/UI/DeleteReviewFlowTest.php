<?php

declare(strict_types=1);

namespace App\Tests\Review\UI;

use App\Catalog\Domain\Model\Product\Product;
use App\Review\Domain\Model\ReviewSummary\ProductReviewSummary;
use App\Review\Domain\Repository\ReviewSummaryRepository;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\ProductReviewFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;

final class DeleteReviewFlowTest extends WebTestCase
{
    use HasBrowser;

    public function testDeleteReviewWithConfirmation(): void
    {
        $review = ProductReviewFactory::createOne(['title' => 'Delete Me']);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/review/' . $review->getPublicId()->value() . '/delete/confirm')
            ->assertSee('Confirm Delete')
            ->click('Delete Review')
            ->assertOn('/review/')
            ->assertSee('Review deleted');
    }

    public function testDeletingPublishedReviewUpdatesProductSummary(): void
    {
        $product = ProductFactory::createOne();
        ProductReviewFactory::new(['product' => $product, 'rating' => 5])->published()->create();
        $review = ProductReviewFactory::new(['product' => $product, 'rating' => 1])->published()->create();

        $summary = $this->summaryFor($product);
        self::assertSame(2, $summary->getReviewCount());
        self::assertSame('3.00', $summary->getAverageRating());

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/review/' . $review->getPublicId()->value() . '/delete/confirm')
            ->click('Delete Review')
            ->assertSee('Review deleted');

        $summary = $this->summaryFor($product);
        self::assertSame(1, $summary->getReviewCount());
        self::assertSame('5.00', $summary->getAverageRating());
    }

    public function testDeletingPendingReviewUpdatesPendingCount(): void
    {
        $product = ProductFactory::createOne();
        $review = ProductReviewFactory::createOne(['product' => $product]);

        self::assertSame(1, $this->summaryFor($product)->getPendingCount());

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/review/' . $review->getPublicId()->value() . '/delete/confirm')
            ->click('Delete Review')
            ->assertSee('Review deleted');

        self::assertSame(0, $this->summaryFor($product)->getPendingCount());
    }

    private function summaryFor(Product $product): ProductReviewSummary
    {
        $summaries = self::getContainer()->get(ReviewSummaryRepository::class);
        self::getContainer()->get('doctrine')->getManager()->clear();

        $summary = $summaries->findByProduct(ProductFactory::repository()->findOrFail($product->getId()));
        self::assertInstanceOf(ProductReviewSummary::class, $summary);

        return $summary;
    }
}
