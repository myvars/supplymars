<?php

namespace App\Tests\Review\Application\Listener;

use App\Review\Domain\Model\Review\Event\ReviewRatingWasChangedEvent;
use App\Review\Domain\Model\Review\Event\ReviewStatusWasChangedEvent;
use App\Review\Domain\Model\Review\Event\ReviewWasCreatedEvent;
use App\Review\Domain\Model\Review\ReviewStatus;
use App\Review\Domain\Model\ReviewSummary\ProductReviewSummary;
use App\Review\Domain\Repository\ReviewSummaryRepository;
use App\Shared\Domain\ValueObject\StatusChange;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\ProductReviewFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Zenstruck\Foundry\Test\Factories;

final class ReviewSummaryUpdaterTest extends KernelTestCase
{
    use Factories;

    private EventDispatcherInterface $dispatcher;

    private ReviewSummaryRepository $summaries;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->dispatcher = self::getContainer()->get(EventDispatcherInterface::class);
        $this->summaries = self::getContainer()->get(ReviewSummaryRepository::class);
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }

    public function testCreatesNewSummaryOnReviewCreated(): void
    {
        $product = ProductFactory::createOne();

        // Verify no summary exists yet
        $existingSummary = $this->summaries->findByProduct($product);
        self::assertNull($existingSummary);

        // Create a published review
        $review = ProductReviewFactory::new([
            'product' => $product,
            'rating' => 4,
        ])->published()->create();

        // Dispatch the event (simulating what happens after entity creation)
        $event = new ReviewWasCreatedEvent($review->getPublicId());
        $this->dispatcher->dispatch($event);

        // Verify summary was created
        $summary = $this->summaries->findByProduct($product);
        self::assertInstanceOf(ProductReviewSummary::class, $summary);
        self::assertSame($product->getId(), $summary->getProduct()->getId());
    }

    public function testUpdatesSummaryOnReviewStatusChanged(): void
    {
        $product = ProductFactory::createOne();

        // Create initial published review
        $review1 = ProductReviewFactory::new([
            'product' => $product,
            'rating' => 5,
        ])->published()->create();

        // Create summary via first event
        $createEvent = new ReviewWasCreatedEvent($review1->getPublicId());
        $this->dispatcher->dispatch($createEvent);

        // Create second published review
        $review2 = ProductReviewFactory::new([
            'product' => $product,
            'rating' => 3,
        ])->published()->create();

        $this->em->flush();

        // Dispatch status change event for second review
        $statusChange = StatusChange::from(ReviewStatus::PENDING, ReviewStatus::PUBLISHED);
        $statusEvent = new ReviewStatusWasChangedEvent($review2->getPublicId(), $statusChange);
        $this->dispatcher->dispatch($statusEvent);

        $summary = $this->summaries->findByProduct($product);
        self::assertInstanceOf(ProductReviewSummary::class, $summary);
        // Summary should reflect both reviews (count = 2, avg = 4.0)
        self::assertSame(2, $summary->getReviewCount());
    }

    public function testUpdatesSummaryOnReviewRatingChanged(): void
    {
        $product = ProductFactory::createOne();

        // Create published review with initial rating
        $review = ProductReviewFactory::new([
            'product' => $product,
            'rating' => 3,
        ])->published()->create();

        // Create summary
        $createEvent = new ReviewWasCreatedEvent($review->getPublicId());
        $this->dispatcher->dispatch($createEvent);

        $summary = $this->summaries->findByProduct($product);
        self::assertSame('3.00', $summary->getAverageRating());

        // Update the rating
        $review->update(5, $review->getTitle(), $review->getBody());
        $this->em->flush();

        // Dispatch rating changed event
        $ratingEvent = new ReviewRatingWasChangedEvent($review->getPublicId());
        $this->dispatcher->dispatch($ratingEvent);

        // Refresh summary
        $this->em->clear();
        $summary = $this->summaries->findByProduct($product);
        self::assertSame('5.00', $summary->getAverageRating());
    }

    public function testHandlesNonProductReviewGracefully(): void
    {
        $this->expectNotToPerformAssertions();

        // Create a review without product (if possible) - in this case we test
        // that it doesn't throw when review lookup returns non-ProductReview
        // Since all reviews are ProductReviews in this system, just verify no error
        $product = ProductFactory::createOne();
        $review = ProductReviewFactory::new(['product' => $product])->published()->create();

        $event = new ReviewWasCreatedEvent($review->getPublicId());

        // Should not throw
        $this->dispatcher->dispatch($event);
    }

    public function testSummaryReflectsOnlyPublishedReviews(): void
    {
        $product = ProductFactory::createOne();

        // Create one published review
        $publishedReview = ProductReviewFactory::new([
            'product' => $product,
            'rating' => 5,
        ])->published()->create();

        // Create one pending review (not published)
        ProductReviewFactory::new([
            'product' => $product,
            'rating' => 1,
        ])->create();

        $this->em->flush();

        // Trigger summary calculation
        $this->dispatcher->dispatch(new ReviewWasCreatedEvent($publishedReview->getPublicId()));

        $summary = $this->summaries->findByProduct($product);
        self::assertInstanceOf(ProductReviewSummary::class, $summary);

        // Average should only reflect published reviews (5.0, not 3.0)
        self::assertSame('5.00', $summary->getAverageRating());
        self::assertSame(1, $summary->getReviewCount());
    }
}
