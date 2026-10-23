<?php

declare(strict_types=1);

namespace App\Review\Domain\Model\Review;

/**
 * Why a customer may not review a product from an order. Returned by ProductReview::ineligibility().
 */
enum ReviewIneligibility
{
    case ORDER_NOT_OWNED;
    case ORDER_NOT_DELIVERED;
    case PRODUCT_NOT_IN_ORDER;

    public function message(): string
    {
        return match ($this) {
            self::ORDER_NOT_OWNED => 'Order does not belong to this customer.',
            self::ORDER_NOT_DELIVERED, self::PRODUCT_NOT_IN_ORDER => 'Delivered product not found in this order.',
        };
    }
}
