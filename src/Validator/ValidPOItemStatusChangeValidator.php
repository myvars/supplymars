<?php

namespace App\Validator;

use App\DTO\ChangePurchaseOrderItemStatusDto;
use App\Entity\PurchaseOrderItem;
use App\Repository\PurchaseOrderItemRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ValidPOItemStatusChangeValidator extends ConstraintValidator
{
    public function __construct(private readonly PurchaseOrderItemRepository $purchaseOrderItemRepository)
    {
    }

    public function validate($value, Constraint $constraint): void
    {
        assert($constraint instanceof ValidPOItemStatusChange);

        $purchaseOrderItemStatusChangeDto = $this->context->getObject();
        assert($purchaseOrderItemStatusChangeDto instanceof ChangePurchaseOrderItemStatusDto);

        $purchaseOrderItem = $this->purchaseOrderItemRepository->find($purchaseOrderItemStatusChangeDto->getId());
        assert($purchaseOrderItem instanceof PurchaseOrderItem);

        if ($purchaseOrderItem->getStatus() === $value) {
            return;
        }

        if (!$purchaseOrderItem->getStatus()->canTransitionTo($value)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value->value)
                ->addViolation();
        }
    }
}