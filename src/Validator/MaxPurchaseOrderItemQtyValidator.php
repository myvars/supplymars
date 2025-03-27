<?php

namespace App\Validator;

use App\DTO\EditPurchaseOrderItemDto;
use App\Entity\PurchaseOrderItem;
use App\Repository\PurchaseOrderItemRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class MaxPurchaseOrderItemQtyValidator extends ConstraintValidator
{
    public function __construct(private readonly PurchaseOrderItemRepository $purchaseOrderItemRepository)
    {
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof MaxPurchaseOrderItemQty) {
            throw new \InvalidArgumentException(
                'Constraint must be an instance of ' . MaxPurchaseOrderItemQty::class
            );
        }

        if (null === $value || '' === $value) {
            return;
        }

        $purchaseOrderItemDto = $this->context->getObject();
        if (!$purchaseOrderItemDto instanceof EditPurchaseOrderItemDto) {
            throw new \InvalidArgumentException(
                'The MaxPurchaseOrderItemQty constraint can only be used on EditPurchaseOrderItemDto'
            );
        }

        $purchaseOrderItem = $this->purchaseOrderItemRepository->find($purchaseOrderItemDto->getId());
        if (!$purchaseOrderItem instanceof PurchaseOrderItem) {
            throw new \InvalidArgumentException(
                'The MaxPurchaseOrderItemQty constraint can only be used with a valid purchaseOrderItemId'
            );
        }

        $maxQuantity = $purchaseOrderItem->getMaxQuantity();

        if ($value > $maxQuantity) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ maxQuantity }}', $maxQuantity)
                ->addViolation();
        }
    }
}
