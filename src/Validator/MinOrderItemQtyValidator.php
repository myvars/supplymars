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

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof MinOrderItemQty) {
            throw new \InvalidArgumentException('Constraint must be instance of '.MinOrderItemQty::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        $editOrderItemDto = $this->context->getObject();
        if (!$editOrderItemDto instanceof EditOrderItemDto) {
            throw new \InvalidArgumentException('Object must be instance of '.EditOrderItemDto::class);
        }

        $customerOrderItem = $this->customerOrderItemRepository->find($editOrderItemDto->getId());
        if (!$customerOrderItem instanceof CustomerOrderItem) {
            throw new \InvalidArgumentException('CustomerOrderItem not found');
        }

        $minQuantity = $customerOrderItem->getQtyAddedToPurchaseOrders();

        if ($value < $minQuantity) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ minQuantity }}', $minQuantity)
                ->addViolation();
        }
    }
}
