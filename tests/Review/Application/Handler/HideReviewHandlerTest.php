<?php

namespace App\Tests\Review\Application\Handler;

use App\Review\Application\Command\HideReview;
use App\Review\Application\Handler\HideReviewHandler;
use App\Review\Domain\Model\Review\ReviewStatus;
use App\Review\Domain\Repository\ReviewRepository;
use App\Tests\Shared\Factory\ProductReviewFactory;
use App\Tests\Shared\Story\StaffUserStory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\Test\Factories;

class HideReviewHandlerTest extends KernelTestCase
{
    use Factories;

    private HideReviewHandler $handler;

    private ReviewRepository $reviews;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(HideReviewHandler::class);
        $this->reviews = self::getContainer()->get(ReviewRepository::class);
    }

    #[WithStory(StaffUserStory::class)]
    public function testSuccessfulHide(): void
    {
        $review = ProductReviewFactory::new()->published()->create();
        $command = new HideReview($review->getPublicId());

        $result = ($this->handler)($command);

        self::assertTrue($result->ok);
        self::assertSame('Review hidden', $result->message);

        $persisted = $this->reviews->getByPublicId($review->getPublicId());
        self::assertSame(ReviewStatus::HIDDEN, $persisted->getStatus());
    }

    #[WithStory(StaffUserStory::class)]
    public function testFailsWhenNotPublished(): void
    {
        $review = ProductReviewFactory::createOne(); // PENDING
        $command = new HideReview($review->getPublicId());

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Cannot transition', $result->message);
    }
}
