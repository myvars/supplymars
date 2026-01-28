<?php

namespace App\Review\UI\Http\Form\Model;

use App\Review\Domain\Model\Review\ProductReview;
use App\Review\Domain\Model\Review\RejectionReason;
use Symfony\Component\Validator\Constraints as Assert;

final class RejectReviewForm
{
    public ?string $id = null;

    #[Assert\NotBlank(message: 'Please select a rejection reason')]
    public ?RejectionReason $reason = null;

    public ?string $notes = null;

    public static function fromEntity(ProductReview $review): self
    {
        $form = new self();
        $form->id = $review->getPublicId()->value();
        $form->reason = $review->getRejectionReason();
        $form->notes = $review->getModerationNotes();

        return $form;
    }
}
