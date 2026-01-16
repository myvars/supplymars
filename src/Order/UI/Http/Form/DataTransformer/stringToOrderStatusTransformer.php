<?php

namespace App\Order\UI\Http\Form\DataTransformer;

use App\Order\Domain\Model\Order\OrderStatus;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

final readonly class stringToOrderStatusTransformer implements DataTransformerInterface
{
    // Transforms a string (status) to an enum (OrderStatus)
    public function transform(mixed $value): ?OrderStatus
    {
        if (null === $value) {
            return null;
        }

        try {
            return OrderStatus::from(strtoupper((string) $value));
        } catch (\InvalidArgumentException) {
            throw new TransformationFailedException('Invalid status value: ' . $value);
        }
    }

    // Transforms an enum (OrderStatus) to a string (status)
    public function reverseTransform(mixed $value): ?string
    {
        if (!$value) {
            return null;
        }

        if (!$value instanceof OrderStatus) {
            throw new TransformationFailedException('Expected an OrderStatus.');
        }

        return strtolower($value->value);
    }
}
