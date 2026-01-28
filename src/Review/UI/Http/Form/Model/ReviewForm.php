<?php

namespace App\Review\UI\Http\Form\Model;

use App\Review\Domain\Model\Review\ProductReview;
use App\Review\UI\Http\Validation\ValidReviewEligibility;
use Symfony\Component\Validator\Constraints as Assert;

#[ValidReviewEligibility]
final class ReviewForm
{
    public ?string $id = null;

    #[Assert\NotBlank]
    public ?int $customerId = null;

    #[Assert\NotBlank]
    public ?int $productId = null;

    #[Assert\NotBlank]
    public ?int $orderId = null;

    #[Assert\NotBlank]
    #[Assert\Range(min: 1, max: 5)]
    public ?int $rating = null;

    #[Assert\Length(max: 255)]
    public ?string $title = null;

    #[Assert\Length(max: 2000)]
    public ?string $body = null;

    public static function fromEntity(ProductReview $review): self
    {
        $form = new self();
        $form->id = $review->getPublicId()->value();
        $form->customerId = $review->getCustomer()->getId();
        $form->productId = $review->getProduct()->getId();
        $form->orderId = $review->getCustomerOrder()->getId();
        $form->rating = $review->getRating();
        $form->title = $review->getTitle();
        $form->body = $review->getBody();

        return $form;
    }
}
