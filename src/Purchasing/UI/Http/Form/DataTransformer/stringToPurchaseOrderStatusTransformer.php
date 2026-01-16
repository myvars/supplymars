<?php

namespace App\Purchasing\UI\Http\Form\DataTransformer;

use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

final readonly class stringToPurchaseOrderStatusTransformer implements DataTransformerInterface
{
    // Transforms a string (status) to an enum (PurchaseOrderStatus)
    public function transform(mixed $value): ?PurchaseOrderStatus
    {
        if (null === $value) {
            return null;
        }

        try {
            return PurchaseOrderStatus::from(strtoupper((string) $value));
        } catch (\InvalidArgumentException) {
            throw new TransformationFailedException('Invalid status value: ' . $value);
        }
    }

    // Transforms an enum (PurchaseOrderStatus) to a string (status)
    public function reverseTransform(mixed $value): ?string
    {
        if (!$value) {
            return null;
        }

        if (!$value instanceof PurchaseOrderStatus) {
            throw new TransformationFailedException('Expected a PurchaseOrderStatus.');
        }

        return strtolower($value->value);
    }
}
