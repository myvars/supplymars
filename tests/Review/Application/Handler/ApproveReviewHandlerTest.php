<?php

namespace App\Tests\Review\Application\Handler;

use App\Review\Application\Command\ApproveReview;
use App\Review\Application\Handler\ApproveReviewHandler;
use App\Review\Domain\Model\Review\ReviewPublicId;
use App\Review\Domain\Model\Review\ReviewStatus;
use App\Review\Domain\Repository\ReviewRepository;
use App\Tests\Shared\Factory\ProductReviewFactory;
use App\Tests\Shared\Story\StaffUserStory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\Test\Factories;

class ApproveReviewHandlerTest extends KernelTestCase
{
    use Factories;

    private ApproveReviewHandler $handler;

    private ReviewRepository $reviews;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(ApproveReviewHandler::class);
        $this->reviews = self::getContainer()->get(ReviewRepository::class);
    }

    #[WithStory(StaffUserStory::class)]
    public function testSuccessfulApproval(): void
    {
        $review = ProductReviewFactory::createOne();
        $command = new ApproveReview($review->getPublicId());

        $result = ($this->handler)($command);

        self::assertTrue($result->ok);
        self::assertSame('Review approved', $result->message);

        $persisted = $this->reviews->getByPublicId($review->getPublicId());
        self::assertSame(ReviewStatus::PUBLISHED, $persisted->getStatus());
    }

    #[WithStory(StaffUserStory::class)]
    public function testFailsWhenReviewNotFound(): void
    {
        $command = new ApproveReview(ReviewPublicId::new());

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Review not found', $result->message);
    }
}
