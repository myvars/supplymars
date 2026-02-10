<?php

namespace App\Note\UI\Http\Form\DataTransformer;

use App\Note\Domain\Model\Ticket\TicketStatus;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * @implements DataTransformerInterface<string|null, TicketStatus|null>
 */
final readonly class StringToTicketStatusTransformer implements DataTransformerInterface
{
    public function transform(mixed $value): ?TicketStatus
    {
        if ($value === null) {
            return null;
        }

        try {
            return TicketStatus::from(strtoupper((string) $value));
        } catch (\ValueError) {
            throw new TransformationFailedException('Invalid status value: ' . $value);
        }
    }

    public function reverseTransform(mixed $value): ?string
    {
        if (!$value) {
            return null;
        }

        if (!$value instanceof TicketStatus) {
            throw new TransformationFailedException('Expected a TicketStatus.');
        }

        return strtolower($value->value);
    }
}
