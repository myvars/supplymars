<?php

namespace App\Review\UI\Http\Form\Mapper;

use App\Review\Application\Command\CreateReview;
use App\Review\UI\Http\Form\Model\ReviewForm;

final class CreateReviewMapper
{
    public function __invoke(ReviewForm $data): CreateReview
    {
        return new CreateReview(
            customerId: $data->customerId,
            productId: $data->productId,
            orderId: $data->orderId,
            rating: $data->rating,
            title: $data->title,
            body: $data->body,
        );
    }
}
