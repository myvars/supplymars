<?php

namespace App\Purchasing\UI\Http\Validation;

use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItem;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItemPublicId;
use App\Purchasing\Domain\Repository\PurchaseOrderItemRepository;
use App\Purchasing\UI\Http\Form\Model\PurchaseOrderItemQuantityForm;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

final class MaxPurchaseOrderItemQtyValidator extends ConstraintValidator
{
    public function __construct(private readonly PurchaseOrderItemRepository $purchaseOrderItems)
    {
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof MaxPurchaseOrderItemQty) {
            throw new \InvalidArgumentException('Constraint must be an instance of ' . MaxPurchaseOrderItemQty::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        $data = $this->context->getObject();
        if (!$data instanceof PurchaseOrderItemQuantityForm) {
            throw new \InvalidArgumentException('The MaxPurchaseOrderItemQty constraint can only be used on PurchaseOrderItemQuantityForm');
        }

        $purchaseOrderItem = $this->purchaseOrderItems->getByPublicId(PurchaseOrderItemPublicId::fromString($data->id));
        if (!$purchaseOrderItem instanceof PurchaseOrderItem) {
            throw new \InvalidArgumentException('The MaxPurchaseOrderItemQty constraint can only be used with a valid purchaseOrderItemId');
        }

        $maxQuantity = $purchaseOrderItem->getMaxQuantity();

        if ($value > $maxQuantity) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ maxQuantity }}', $maxQuantity)
                ->addViolation();
        }
    }
}
