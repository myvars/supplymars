<?php

declare(strict_types=1);

namespace App\Review\UI\Http\Validation;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class ValidReviewEligibility extends Constraint
{
    public string $orderNotFoundMessage = 'Order not found.';

    public string $orderNotOwnedMessage = 'Order does not belong to this customer.';

    public string $noDeliveredItemMessage = 'Delivered product not found in this order.';

    public string $duplicateReviewMessage = 'A review already exists for this customer and product.';

    #[\Override]
    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
