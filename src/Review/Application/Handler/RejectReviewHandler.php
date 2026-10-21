<?php

declare(strict_types=1);

namespace App\Review\Application\Handler;

use App\Customer\Infrastructure\Security\CurrentUserProvider;
use App\Review\Application\Command\RejectReview;
use App\Review\Domain\Model\Review\ProductReview;
use App\Review\Domain\Repository\ReviewRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Application\Result;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class RejectReviewHandler
{
    public function __construct(
        private ReviewRepository $reviews,
        private FlusherInterface $flusher,
        private CurrentUserProvider $userProvider,
        private ValidatorInterface $validator,
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

        $errors = $this->validator->validate($review);
        if (count($errors) > 0) {
            return Result::fail((string) $errors[0]->getMessage());
        }

        $this->flusher->flush();

        return Result::ok('Review rejected');
    }
}
