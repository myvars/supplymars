<?php

namespace App\Shared\UI\Http\Validation;

use App\Shared\Application\Search\DateRangeSearchCriteriaInterface;
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

        if (!$value instanceof DateRangeSearchCriteriaInterface) {
            throw new UnexpectedValueException($value, DateRangeSearchCriteriaInterface::class);
        }

        $startDate = $value->getStartDate();
        $endDate = $value->getEndDate();

        if ($startDate && $endDate) {
            $start = \DateTime::createFromFormat('Y-m-d', $startDate);
            $end = \DateTime::createFromFormat('Y-m-d', $endDate);

            if ($start && $end && $start > $end) {
                $this->context->buildViolation($constraint->message)
                    ->atPath('startDate')
                    ->addViolation();
            }
        }
    }
}
