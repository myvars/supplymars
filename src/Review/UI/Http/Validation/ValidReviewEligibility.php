<?php

declare(strict_types=1);

namespace App\Review\UI\Http\Validation;

use Symfony\Component\Validator\Constraint;

/**
 * Puts the review eligibility rule's message on the form field it concerns. The rule itself is
 * ProductReview::ineligibility(), which CreateReviewHandler asks too, so a caller that skips the
 * form is held to it.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class ValidReviewEligibility extends Constraint
{
    public string $orderNotFoundMessage = 'Order not found.';

    public string $duplicateReviewMessage = 'A review already exists for this customer and product.';

    #[\Override]
    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
