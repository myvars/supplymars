<?php

namespace App\Order\UI\Http\Validation;

use App\Order\Domain\Model\Order\CustomerOrderItem;
use App\Order\Domain\Model\Order\OrderItemPublicId;
use App\Order\Domain\Repository\OrderItemRepository;
use App\Order\UI\Http\Form\Model\UpdateOrderItemForm;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

final class MinOrderItemQtyValidator extends ConstraintValidator
{
    public function __construct(private readonly OrderItemRepository $orderItems)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof MinOrderItemQty) {
            throw new \InvalidArgumentException('Constraint must be instance of ' . MinOrderItemQty::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        $formModel = $this->context->getObject();
        if (!$formModel instanceof UpdateOrderItemForm) {
            throw new \InvalidArgumentException('Object must be instance of ' . UpdateOrderItemForm::class);
        }

        $orderItem = $this->orderItems->getByPublicId(OrderItemPublicId::fromString($formModel->orderItemId));
        if (!$orderItem instanceof CustomerOrderItem) {
            throw new \InvalidArgumentException('CustomerOrderItem not found');
        }

        $minQuantity = $orderItem->getQtyAddedToPurchaseOrders();
        if ((int) $value < $minQuantity) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ minQuantity }}', (string) $minQuantity)
                ->addViolation();
        }
    }
}
