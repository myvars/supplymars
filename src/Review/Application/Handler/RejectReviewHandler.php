<?php

namespace App\Review\Application\Handler;

use App\Review\Application\Command\RejectReview;
use App\Review\Domain\Model\Review\ProductReview;
use App\Review\Domain\Repository\ReviewRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Application\Result;
use App\Shared\Infrastructure\Security\CurrentUserProvider;

final readonly class RejectReviewHandler
{
    public function __construct(
        private ReviewRepository $reviews,
        private FlusherInterface $flusher,
        private CurrentUserProvider $userProvider,
    ) {
    }

    public function __invoke(RejectReview $command): Result
    {
        $review = $this->reviews->getByPublicId($command->id);
        if (!$review instanceof ProductReview) {
            return Result::fail('Review not found.');
        }

        try {
            $moderator = $this->userProvider->get();
        } catch (\RuntimeException) {
            return Result::fail('Moderator not found.');
        }

        try {
            $review->reject($moderator, $command->reason, $command->notes);
        } catch (\LogicException $logicException) {
            return Result::fail($logicException->getMessage());
        }

        $this->flusher->flush();

        return Result::ok('Review rejected');
    }
}
