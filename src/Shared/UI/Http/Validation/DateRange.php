<?php

declare(strict_types=1);

namespace App\Shared\UI\Http\Validation;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class DateRange extends Constraint
{
    /*
     * Any public properties become valid options for the annotation.
     * Then, use these in your validator class.
     */
    public string $message = 'The start date must be earlier than the end date';

    #[\Override]
    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;  // Ensure the constraint targets the class
    }
}
