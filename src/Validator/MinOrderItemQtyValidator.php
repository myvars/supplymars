<?php

namespace App\Validator;

use App\DTO\EditOrderItemDto;
use App\Entity\CustomerOrderItem;
use App\Repository\CustomerOrderItemRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class MinOrderItemQtyValidator extends ConstraintValidator
{
    public function __construct(private readonly CustomerOrderItemRepository $customerOrderItemRepository)
    {
    }

    public function validate($value, Constraint $constraint)
    {
        assert($constraint instanceof MinOrderItemQty);

        if (null === $value || '' === $value) {
            return;
        }

        $editOrderItemDto = $this->context->getObject();
        assert($editOrderItemDto instanceof EditOrderItemDto);

        $customerOrderItem = $this->customerOrderItemRepository->find($editOrderItemDto->getId());
        assert($customerOrderItem instanceof CustomerOrderItem);

        $minQuantity = $customerOrderItem->getQtyAddedToPurchaseOrders();

        if ($value < $minQuantity) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ minQuantity }}', $minQuantity)
                ->addViolation();
        }
    }
}
