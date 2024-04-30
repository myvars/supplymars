<?php

namespace App\Validator;

use App\Repository\UserRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ValidCustomerIdValidator extends ConstraintValidator
{
    public function __construct(private readonly UserRepository $userRepository)
    {
    }

    public function validate($value, Constraint $constraint)
    {
        /* @var ValidCustomerId $constraint */

        if (!is_numeric($value) || 0 === (int)$value) {
            return;
        }

        if ($user = $this->userRepository->find($value)) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ value }}', $value)
            ->addViolation();
    }
}
