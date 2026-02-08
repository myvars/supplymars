<?php

namespace App\Tests\Review\Application\Handler;

use App\Review\Application\Command\DeleteReview;
use App\Review\Application\Handler\DeleteReviewHandler;
use App\Review\Domain\Model\Review\ReviewPublicId;
use App\Review\Domain\Repository\ReviewRepository;
use App\Tests\Shared\Factory\ProductReviewFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

final class DeleteReviewHandlerTest extends KernelTestCase
{
    use Factories;

    private DeleteReviewHandler $handler;

    private ReviewRepository $reviews;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(DeleteReviewHandler::class);
        $this->reviews = self::getContainer()->get(ReviewRepository::class);
    }

    public function testSuccessfulDeletion(): void
    {
        $review = ProductReviewFactory::createOne();
        $publicId = $review->getPublicId();

        $command = new DeleteReview($publicId);

        $result = ($this->handler)($command);

        self::assertTrue($result->ok);
        self::assertSame('Review deleted', $result->message);
        self::assertNull($this->reviews->getByPublicId($publicId));
    }

    public function testFailsWhenReviewNotFound(): void
    {
        $command = new DeleteReview(ReviewPublicId::new());

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Review not found', $result->message);
    }
}
