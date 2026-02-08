<?php

namespace App\Tests\Review\Application\Handler;

use App\Review\Application\Command\UpdateReview;
use App\Review\Application\Handler\UpdateReviewHandler;
use App\Review\Domain\Model\Review\RejectionReason;
use App\Review\Domain\Model\Review\ReviewPublicId;
use App\Review\Domain\Repository\ReviewRepository;
use App\Tests\Shared\Factory\ProductReviewFactory;
use App\Tests\Shared\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

final class UpdateReviewHandlerTest extends KernelTestCase
{
    use Factories;

    private UpdateReviewHandler $handler;

    private ReviewRepository $reviews;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(UpdateReviewHandler::class);
        $this->reviews = self::getContainer()->get(ReviewRepository::class);
    }

    public function testSuccessfulUpdate(): void
    {
        $review = ProductReviewFactory::createOne([
            'rating' => 3,
            'title' => 'Original Title',
            'body' => 'Original body text',
        ]);

        $command = new UpdateReview(
            id: $review->getPublicId(),
            rating: 5,
            title: 'Updated Title',
            body: 'Updated body text',
        );

        $result = ($this->handler)($command);

        self::assertTrue($result->ok);
        self::assertSame('Review updated', $result->message);

        $persisted = $this->reviews->getByPublicId($review->getPublicId());
        self::assertSame(5, $persisted->getRating());
        self::assertSame('Updated Title', $persisted->getTitle());
        self::assertSame('Updated body text', $persisted->getBody());
    }

    public function testFailsWhenReviewNotFound(): void
    {
        $command = new UpdateReview(
            id: ReviewPublicId::new(),
            rating: 5,
            title: 'Title',
            body: 'Body',
        );

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Review not found', $result->message);
    }

    public function testFailsWhenReviewStatusDoesNotAllowEdit(): void
    {
        // Create a review and reject it to get REJECTED status
        $review = ProductReviewFactory::createOne();
        $moderator = UserFactory::new()->asStaff()->create();
        $review->reject($moderator, RejectionReason::SPAM, 'Spam content');

        // Flush to persist the status change
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->flush();

        $command = new UpdateReview(
            id: $review->getPublicId(),
            rating: 5,
            title: 'Title',
            body: 'Body',
        );

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('cannot be edited', $result->message);
    }

    public function testCanUpdatePublishedReview(): void
    {
        $review = ProductReviewFactory::new(['rating' => 3])->published()->create();

        $command = new UpdateReview(
            id: $review->getPublicId(),
            rating: 4,
            title: 'New Title',
            body: 'New body',
        );

        $result = ($this->handler)($command);

        self::assertTrue($result->ok);

        $persisted = $this->reviews->getByPublicId($review->getPublicId());
        self::assertSame(4, $persisted->getRating());
    }
}
