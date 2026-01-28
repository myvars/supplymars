<?php

namespace App\Review\UI\Http\Form\Mapper;

use App\Review\Application\Command\UpdateReview;
use App\Review\Domain\Model\Review\ReviewPublicId;
use App\Review\UI\Http\Form\Model\ReviewForm;

final class UpdateReviewMapper
{
    public function __invoke(ReviewForm $data): UpdateReview
    {
        return new UpdateReview(
            id: ReviewPublicId::fromString($data->id),
            rating: $data->rating,
            title: $data->title,
            body: $data->body,
        );
    }
}
