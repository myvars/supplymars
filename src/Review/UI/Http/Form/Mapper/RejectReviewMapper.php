<?php

namespace App\Review\UI\Http\Form\Mapper;

use App\Review\Application\Command\RejectReview;
use App\Review\Domain\Model\Review\ReviewPublicId;
use App\Review\UI\Http\Form\Model\RejectReviewForm;

final class RejectReviewMapper
{
    public function __invoke(RejectReviewForm $data): RejectReview
    {
        return new RejectReview(
            id: ReviewPublicId::fromString($data->id),
            reason: $data->reason,
            notes: $data->notes,
        );
    }
}
