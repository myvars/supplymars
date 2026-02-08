<?php

namespace App\Tests\Review\Application\Handler;

use App\Review\Application\Command\RepublishReview;
use App\Review\Application\Handler\RepublishReviewHandler;
use App\Review\Domain\Model\Review\RejectionReason;
use App\Review\Domain\Model\Review\ReviewPublicId;
use App\Review\Domain\Model\Review\ReviewStatus;
use App\Review\Domain\Repository\ReviewRepository;
use App\Tests\Shared\Factory\ProductReviewFactory;
use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\Story\StaffUserStory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\Test\Factories;

final class RepublishReviewHandlerTest extends KernelTestCase
{
    use Factories;

    private RepublishReviewHandler $handler;

    private ReviewRepository $reviews;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(RepublishReviewHandler::class);
        $this->reviews = self::getContainer()->get(ReviewRepository::class);
    }

    #[WithStory(StaffUserStory::class)]
    public function testSuccessfulRepublish(): void
    {
        $review = ProductReviewFactory::new()->hidden()->create();

        $command = new RepublishReview($review->getPublicId());

        $result = ($this->handler)($command);

        self::assertTrue($result->ok);
        self::assertSame('Review republished', $result->message);

        $persisted = $this->reviews->getByPublicId($review->getPublicId());
        self::assertSame(ReviewStatus::PUBLISHED, $persisted->getStatus());
    }

    #[WithStory(StaffUserStory::class)]
    public function testFailsWhenReviewNotFound(): void
    {
        $command = new RepublishReview(ReviewPublicId::new());

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Review not found', $result->message);
    }

    #[WithStory(StaffUserStory::class)]
    public function testFailsWhenReviewIsRejected(): void
    {
        // REJECTED is a terminal state - cannot transition to PUBLISHED
        $review = ProductReviewFactory::createOne();
        $moderator = UserFactory::new()->asStaff()->create();
        $review->reject($moderator, RejectionReason::SPAM, 'Spam');

        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->flush();

        $command = new RepublishReview($review->getPublicId());

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Cannot transition', $result->message);
    }

    public function testFailsWhenNoModeratorAuthenticated(): void
    {
        $review = ProductReviewFactory::new()->hidden()->create();

        $command = new RepublishReview($review->getPublicId());

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Moderator not found', $result->message);
    }
}
