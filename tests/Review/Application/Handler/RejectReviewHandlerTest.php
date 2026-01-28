<?php

namespace App\Tests\Review\Application\Handler;

use App\Review\Application\Command\RejectReview;
use App\Review\Application\Handler\RejectReviewHandler;
use App\Review\Domain\Model\Review\RejectionReason;
use App\Review\Domain\Model\Review\ReviewStatus;
use App\Review\Domain\Repository\ReviewRepository;
use App\Tests\Shared\Factory\ProductReviewFactory;
use App\Tests\Shared\Story\StaffUserStory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\Test\Factories;

class RejectReviewHandlerTest extends KernelTestCase
{
    use Factories;

    private RejectReviewHandler $handler;

    private ReviewRepository $reviews;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(RejectReviewHandler::class);
        $this->reviews = self::getContainer()->get(ReviewRepository::class);
    }

    #[WithStory(StaffUserStory::class)]
    public function testSuccessfulRejection(): void
    {
        $review = ProductReviewFactory::createOne();
        $command = new RejectReview(
            $review->getPublicId(),
            RejectionReason::SPAM,
            'Clearly spam content',
        );

        $result = ($this->handler)($command);

        self::assertTrue($result->ok);
        self::assertSame('Review rejected', $result->message);

        $persisted = $this->reviews->getByPublicId($review->getPublicId());
        self::assertSame(ReviewStatus::REJECTED, $persisted->getStatus());
        self::assertSame(RejectionReason::SPAM, $persisted->getRejectionReason());
        self::assertSame('Clearly spam content', $persisted->getModerationNotes());
    }
}
