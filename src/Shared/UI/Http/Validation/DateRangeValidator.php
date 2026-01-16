<?php

namespace App\Shared\UI\Http\Validation;

use App\Shared\Application\Search\SearchCriteria;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class DateRangeValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof DateRange) {
            throw new UnexpectedTypeException($constraint, DateRange::class);
        }

        if (!$value instanceof SearchCriteria) {
            throw new UnexpectedValueException($value, SearchCriteria::class);
        }

        if ($value->startDate && $value->endDate) {
            $startDate = \DateTime::createFromFormat('Y-m-d', $value->startDate);
            $endDate = \DateTime::createFromFormat('Y-m-d', $value->endDate);

            if ($startDate && $endDate && $startDate > $endDate) {
                $this->context->buildViolation($constraint->message)
                    ->atPath('startDate')
                    ->addViolation();
            }
        }
    }
}
