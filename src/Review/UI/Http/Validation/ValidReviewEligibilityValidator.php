<?php

namespace App\Review\UI\Http\Validation;

use App\Catalog\Domain\Model\Product\Product;
use App\Customer\Domain\Model\User\User;
use App\Customer\Domain\Model\User\UserId;
use App\Customer\Domain\Repository\UserRepository;
use App\Order\Domain\Model\Order\CustomerOrder;
use App\Order\Domain\Model\Order\OrderId;
use App\Order\Domain\Model\Order\OrderStatus;
use App\Order\Domain\Repository\OrderRepository;
use App\Review\Domain\Model\Review\ProductReview;
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

        if ($order->getCustomer()->getId() !== $value->customerId) {
            $this->context->buildViolation($constraint->orderNotOwnedMessage)
                ->atPath('orderId')
                ->addViolation();

            return;
        }

        if ($order->getStatus() !== OrderStatus::DELIVERED) {
            $this->context->buildViolation($constraint->noDeliveredItemMessage)
                ->atPath('orderId')
                ->addViolation();

            return;
        }

        $product = $this->findProductInOrder($order, $value->productId);
        if (!$product instanceof Product) {
            $this->context->buildViolation($constraint->noDeliveredItemMessage)
                ->atPath('productId')
                ->addViolation();

            return;
        }

        // Check for duplicate review
        $customer = $this->users->get(UserId::fromInt($value->customerId));
        if ($customer instanceof User) {
            $existing = $this->reviews->findByCustomerAndProduct($customer, $product);

            if ($existing instanceof ProductReview) {
                $this->context->buildViolation($constraint->duplicateReviewMessage)
                    ->atPath('productId')
                    ->addViolation();
            }
        }
    }

    private function findProductInOrder(CustomerOrder $order, int $productId): ?Product
    {
        foreach ($order->getCustomerOrderItems() as $item) {
            if ($item->getProduct()->getId() === $productId) {
                return $item->getProduct();
            }
        }

        return null;
    }
}
