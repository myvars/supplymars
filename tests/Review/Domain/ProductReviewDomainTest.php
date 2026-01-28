<?php

namespace App\Tests\Review\Domain;

use App\Review\Domain\Model\Review\RejectionReason;
use App\Review\Domain\Model\Review\ReviewStatus;
use App\Tests\Shared\Factory\ProductReviewFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class ProductReviewDomainTest extends KernelTestCase
{
    use Factories;

    public function testCreateSetsDefaultStatus(): void
    {
        $review = ProductReviewFactory::createOne();

        self::assertSame(ReviewStatus::PENDING, $review->getStatus());
    }

    public function testUpdateModifiesRatingTitleBody(): void
    {
        $review = ProductReviewFactory::createOne([
            'rating' => 3,
            'title' => 'Original',
            'body' => 'Original body',
        ]);

        $review->update(5, 'Updated title', 'Updated body');

        self::assertSame(5, $review->getRating());
        self::assertSame('Updated title', $review->getTitle());
        self::assertSame('Updated body', $review->getBody());
    }

    public function testApproveChangesStatusToPublished(): void
    {
        $review = ProductReviewFactory::createOne();
        $moderator = UserFactory::new()->asStaff()->create();

        $review->approve($moderator);

        self::assertSame(ReviewStatus::PUBLISHED, $review->getStatus());
        self::assertNotNull($review->getPublishedAt());
        self::assertSame($moderator->getId(), $review->getModeratedBy()->getId());
        self::assertNotNull($review->getModeratedAt());
    }

    public function testRejectChangesStatusToRejected(): void
    {
        $review = ProductReviewFactory::createOne();
        $moderator = UserFactory::new()->asStaff()->create();

        $review->reject($moderator, RejectionReason::SPAM, 'Test notes');

        self::assertSame(ReviewStatus::REJECTED, $review->getStatus());
        self::assertSame(RejectionReason::SPAM, $review->getRejectionReason());
        self::assertSame('Test notes', $review->getModerationNotes());
    }

    public function testHideChangesStatusToHidden(): void
    {
        $review = ProductReviewFactory::createOne();
        $moderator = UserFactory::new()->asStaff()->create();

        $review->approve($moderator);
        $review->hide($moderator);

        self::assertSame(ReviewStatus::HIDDEN, $review->getStatus());
    }

    public function testRepublishChangesStatusToPublished(): void
    {
        $review = ProductReviewFactory::createOne();
        $moderator = UserFactory::new()->asStaff()->create();

        $review->approve($moderator);
        $review->hide($moderator);
        $review->republish($moderator);

        self::assertSame(ReviewStatus::PUBLISHED, $review->getStatus());
    }

    public function testInvalidRatingThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Rating must be between 1 and 5.');

        ProductReviewFactory::createOne(['rating' => 0]);
    }

    public function testRatingAboveFiveThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Rating must be between 1 and 5.');

        ProductReviewFactory::createOne(['rating' => 6]);
    }

    public function testDoubleApproveThrowsLogicException(): void
    {
        $review = ProductReviewFactory::createOne();
        $moderator = UserFactory::new()->asStaff()->create();

        $review->approve($moderator);

        $this->expectException(\LogicException::class);
        $review->approve($moderator);
    }

    public function testRejectThenApproveThrowsLogicException(): void
    {
        $review = ProductReviewFactory::createOne();
        $moderator = UserFactory::new()->asStaff()->create();

        $review->reject($moderator, RejectionReason::SPAM);

        $this->expectException(\LogicException::class);
        $review->approve($moderator);
    }
}
