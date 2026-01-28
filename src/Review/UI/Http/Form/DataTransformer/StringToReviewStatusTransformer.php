<?php

namespace App\Review\UI\Http\Form\DataTransformer;

use App\Review\Domain\Model\Review\ReviewStatus;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * @implements DataTransformerInterface<string|null, ReviewStatus|null>
 */
final readonly class StringToReviewStatusTransformer implements DataTransformerInterface
{
    // Transforms a string (status) to an enum (ReviewStatus)
    public function transform(mixed $value): ?ReviewStatus
    {
        if ($value === null) {
            return null;
        }

        try {
            return ReviewStatus::from(strtoupper((string) $value));
        } catch (\ValueError) {
            throw new TransformationFailedException('Invalid status value: ' . $value);
        }
    }

    // Transforms an enum (ReviewStatus) to a string (status)
    public function reverseTransform(mixed $value): ?string
    {
        if (!$value) {
            return null;
        }

        if (!$value instanceof ReviewStatus) {
            throw new TransformationFailedException('Expected a ReviewStatus.');
        }

        return strtolower($value->value);
    }
}
