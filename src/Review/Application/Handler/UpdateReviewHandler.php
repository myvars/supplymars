<?php

declare(strict_types=1);

namespace App\Review\Application\Handler;

use App\Review\Application\Command\UpdateReview;
use App\Review\Domain\Model\Review\ProductReview;
use App\Review\Domain\Repository\ReviewRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Application\Result;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class UpdateReviewHandler
{
    public function __construct(
        private ReviewRepository $reviews,
        private FlusherInterface $flusher,
        private ValidatorInterface $validator,
    ) {
    }

    public function __invoke(UpdateReview $command): Result
    {
        $review = $this->reviews->getByPublicId($command->id);
        if (!$review instanceof ProductReview) {
            return Result::fail('Review not found.');
        }

        if (!$review->getStatus()->allowEdit()) {
            return Result::fail('Review cannot be edited in its current status.');
        }

        $review->update(
            rating: $command->rating,
            title: $command->title,
            body: $command->body,
        );

        $errors = $this->validator->validate($review);
        if (count($errors) > 0) {
            return Result::fail((string) $errors);
        }

        $this->flusher->flush();

        return Result::ok('Review updated');
    }
}
