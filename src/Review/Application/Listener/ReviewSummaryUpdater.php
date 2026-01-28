<?php

namespace App\Review\Application\Listener;

use App\Review\Domain\Model\Review\Event\ReviewRatingWasChangedEvent;
use App\Review\Domain\Model\Review\Event\ReviewStatusWasChangedEvent;
use App\Review\Domain\Model\Review\Event\ReviewWasCreatedEvent;
use App\Review\Domain\Model\Review\ProductReview;
use App\Review\Domain\Model\Review\ReviewPublicId;
use App\Review\Domain\Model\ReviewSummary\ProductReviewSummary;
use App\Review\Domain\Repository\ReviewRepository;
use App\Review\Domain\Repository\ReviewSummaryRepository;
use App\Review\Infrastructure\Persistence\Doctrine\ReviewDoctrineRepository;
use App\Shared\Application\FlusherInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: ReviewWasCreatedEvent::class, method: 'onReviewCreated')]
#[AsEventListener(event: ReviewStatusWasChangedEvent::class, method: 'onReviewStatusChanged')]
#[AsEventListener(event: ReviewRatingWasChangedEvent::class, method: 'onReviewRatingChanged')]
final readonly class ReviewSummaryUpdater
{
    public function __construct(
        private ReviewRepository $reviews,
        private ReviewSummaryRepository $summaries,
        private FlusherInterface $flusher,
    ) {
    }

    public function onReviewCreated(ReviewWasCreatedEvent $event): void
    {
        $this->recalculateSummary($event->getId());
    }

    public function onReviewStatusChanged(ReviewStatusWasChangedEvent $event): void
    {
        $this->recalculateSummary($event->getId());
    }

    public function onReviewRatingChanged(ReviewRatingWasChangedEvent $event): void
    {
        $this->recalculateSummary($event->getId());
    }

    private function recalculateSummary(ReviewPublicId $reviewId): void
    {
        $review = $this->reviews->getByPublicId($reviewId);
        if (!$review instanceof ProductReview) {
            return;
        }

        $product = $review->getProduct();

        $summary = $this->summaries->findByProduct($product);
        if (!$summary instanceof ProductReviewSummary) {
            $summary = ProductReviewSummary::create($product);
            $this->summaries->add($summary);
        }

        assert($this->reviews instanceof ReviewDoctrineRepository);
        $stats = $this->reviews->getProductReviewStats($product);

        $summary->recalculate(
            reviewCount: $stats['count'],
            averageRating: $stats['average'],
            ratingDistribution: $stats['distribution'],
            pendingCount: $stats['pendingCount'],
        );

        $this->flusher->flush();
    }
}
