<?php

namespace App\Order\UI\Http\Validation;

use App\Customer\Infrastructure\Persistence\Doctrine\UserDoctrineRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

final class ValidCustomerIdValidator extends ConstraintValidator
{
    public function __construct(private readonly UserDoctrineRepository $userRepository)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidCustomerId) {
            throw new \InvalidArgumentException('Expected instance of ' . ValidCustomerId::class);
        }

        if (!is_numeric($value) || 0 === (int) $value) {
            return;
        }

        if ($this->userRepository->find($value)) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ value }}', (string) $value)
            ->addViolation();
    }
}
