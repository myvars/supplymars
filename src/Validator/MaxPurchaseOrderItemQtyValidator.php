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

    public function validate($value, Constraint $constraint)
    {
        assert($constraint instanceof MaxPurchaseOrderItemQty);

        if (null === $value || '' === $value) {
            return;
        }

        $purchaseOrderItemDto = $this->context->getObject();
        assert($purchaseOrderItemDto instanceof EditPurchaseOrderItemDto);

        $purchaseOrderItem = $this->purchaseOrderItemRepository->find($purchaseOrderItemDto->getId());
        assert($purchaseOrderItem instanceof PurchaseOrderItem);

        $maxQuantity = $purchaseOrderItem->getMaxQuantity();

        if ($value > $maxQuantity) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ maxQuantity }}', $maxQuantity)
                ->addViolation();
        }
    }
}
