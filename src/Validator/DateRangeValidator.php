<?php

namespace App\Validator;

use App\DTO\SearchDto\SearchInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class DateRangeValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof DateRange) {
            throw new UnexpectedTypeException($constraint, DateRange::class);
        }

        if (!$value instanceof SearchInterface) {
            throw new UnexpectedValueException($value, SearchInterface::class);
        }

        if ($value->getStartDate() && $value->getEndDate()) {
            $startDate = \DateTime::createFromFormat('Y-m-d', $value->getStartDate());
            $endDate = \DateTime::createFromFormat('Y-m-d', $value->getEndDate());

            if ($startDate && $endDate && $startDate > $endDate) {
                $this->context->buildViolation($constraint->message)
                    ->atPath('startDate')
                    ->addViolation();
            }
        }
    }
}
