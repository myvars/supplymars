<?php

namespace App\Review\Application\Handler;

use App\Review\Application\Command\DeleteReview;
use App\Review\Domain\Model\Review\ProductReview;
use App\Review\Domain\Repository\ReviewRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Application\Result;

final readonly class DeleteReviewHandler
{
    public function __construct(
        private ReviewRepository $reviews,
        private FlusherInterface $flusher,
    ) {
    }

    public function __invoke(DeleteReview $command): Result
    {
        $review = $this->reviews->getByPublicId($command->id);
        if (!$review instanceof ProductReview) {
            return Result::fail('Review not found.');
        }

        $this->reviews->remove($review);
        $this->flusher->flush();

        return Result::ok(message: 'Review deleted');
    }
}
