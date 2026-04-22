<?php

declare(strict_types=1);

namespace App\Shared\UI\Http\Validation;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class Turnstile extends Constraint
{
    public string $message = 'Please complete the security check.';
}
