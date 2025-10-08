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
        if (!$constraint instanceof ValidPOItemStatusChange) {
            throw new \InvalidArgumentException('Constraint must be instance of '.ValidPOItemStatusChange::class);
        }

        $purchaseOrderItemStatusChangeDto = $this->context->getObject();
        if (!$purchaseOrderItemStatusChangeDto instanceof ChangePurchaseOrderItemStatusDto) {
            throw new \InvalidArgumentException('Object must be instance of '.ChangePurchaseOrderItemStatusDto::class);
        }

        $purchaseOrderItem = $this->purchaseOrderItemRepository->find($purchaseOrderItemStatusChangeDto->getId());
        if (!$purchaseOrderItem instanceof PurchaseOrderItem) {
            throw new \InvalidArgumentException('PurchaseOrderItem not found');
        }

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
