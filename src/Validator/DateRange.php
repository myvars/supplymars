<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class DateRange extends Constraint
{
    /*
     * Any public properties become valid options for the annotation.
     * Then, use these in your validator class.
     */
    public string $message = 'The start date must be earlier than the end date';

    public function getTargets(): array|string
    {
        return self::CLASS_CONSTRAINT;  // Ensure the constraint targets the class
    }
}
