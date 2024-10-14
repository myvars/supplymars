<?php

namespace App\Form\DataTransformer;

use App\Enum\PriceModel;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

readonly class stringToPriceModelTransformer implements DataTransformerInterface
{
    // Transforms a string (model) to an enum (PriceModel)
    public function transform(mixed $value): ?PriceModel
    {
        if (null === $value) {
            return null;
        }

        try {
            return PriceModel::from(strtoupper((string) $value));
        } catch (\InvalidArgumentException) {
            throw new TransformationFailedException('Invalid price model value: ' . $value);
        }
    }

    // Transforms an enum (PriceModel) to a string (model)
    public function reverseTransform(mixed $value): ?string
    {
        if (!$value) {
            return null;
        }

        if (!$value instanceof PriceModel) {
            throw new TransformationFailedException('Expected a PriceModel.');
        }

        return strtolower($value->value);
    }
}
