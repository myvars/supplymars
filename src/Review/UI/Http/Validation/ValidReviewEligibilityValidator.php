<?php

declare(strict_types=1);

namespace App\Review\UI\Http\Validation;

use App\Catalog\Domain\Model\Product\Product;
use App\Catalog\Domain\Model\Product\ProductId;
use App\Catalog\Domain\Repository\ProductRepository;
use App\Customer\Domain\Model\User\User;
use App\Customer\Domain\Model\User\UserId;
use App\Customer\Domain\Repository\UserRepository;
use App\Order\Domain\Model\Order\CustomerOrder;
use App\Order\Domain\Model\Order\OrderId;
use App\Order\Domain\Repository\OrderRepository;
use App\Review\Domain\Model\Review\ProductReview;
use App\Review\Domain\Model\Review\ReviewIneligibility;
use App\Review\Domain\Repository\ReviewRepository;
use App\Review\UI\Http\Form\Model\ReviewForm;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

final class ValidReviewEligibilityValidator extends ConstraintValidator
{
    public function __construct(
        private readonly OrderRepository $orders,
        private readonly ReviewRepository $reviews,
        private readonly UserRepository $users,
        private readonly ProductRepository $products,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidReviewEligibility) {
            throw new \InvalidArgumentException('Expected instance of ' . ValidReviewEligibility::class);
        }

        if (!$value instanceof ReviewForm) {
            return;
        }

        // Skip eligibility check on edit (already validated on creation)
        if ($value->id !== null) {
            return;
        }

        if ($value->orderId === null || $value->customerId === null || $value->productId === null) {
            return;
        }

        $order = $this->orders->get(OrderId::fromInt($value->orderId));
        if (!$order instanceof CustomerOrder) {
            $this->context->buildViolation($constraint->orderNotFoundMessage)
                ->atPath('orderId')
                ->addViolation();

            return;
        }

        $customer = $this->users->get(UserId::fromInt($value->customerId));
        $product = $this->products->get(ProductId::fromInt($value->productId));
        if (!$customer instanceof User || !$product instanceof Product) {
            return;
        }

        $ineligibility = ProductReview::ineligibility($customer, $product, $order);
        if ($ineligibility instanceof ReviewIneligibility) {
            $this->context->buildViolation($ineligibility->message())
                ->atPath($ineligibility === ReviewIneligibility::PRODUCT_NOT_IN_ORDER ? 'productId' : 'orderId')
                ->addViolation();

            return;
        }

        if ($this->reviews->findByCustomerAndProduct($customer, $product) instanceof ProductReview) {
            $this->context->buildViolation($constraint->duplicateReviewMessage)
                ->atPath('productId')
                ->addViolation();
        }
    }
}
