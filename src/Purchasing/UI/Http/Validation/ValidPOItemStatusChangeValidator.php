<?php

namespace App\Purchasing\UI\Http\Validation;

use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItem;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItemPublicId;
use App\Purchasing\Domain\Repository\PurchaseOrderItemRepository;
use App\Purchasing\UI\Http\Form\Model\PurchaseOrderItemStatusForm;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

final class ValidPOItemStatusChangeValidator extends ConstraintValidator
{
    public function __construct(private readonly PurchaseOrderItemRepository $purchaseOrderItems)
    {
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidPOItemStatusChange) {
            throw new \InvalidArgumentException('Constraint must be instance of '.ValidPOItemStatusChange::class);
        }

        $data = $this->context->getObject();
        if (!$data instanceof PurchaseOrderItemStatusForm) {
            throw new \InvalidArgumentException(
                'The ValidPOItemStatus constraint can only be used on PurchaseOrderItemStatusForm'
            );
        }

        $purchaseOrderItem = $this->purchaseOrderItems->getByPublicId(PurchaseOrderItemPublicId::fromString($data->id));
        if (!$purchaseOrderItem instanceof PurchaseOrderItem) {
            throw new \InvalidArgumentException(
                'The ValidPOItemStatus constraint can only be used with a valid purchaseOrderItemId'
            );
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
