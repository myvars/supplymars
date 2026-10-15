<?php

declare(strict_types=1);

namespace App\Order\UI\Http\Validation;

use App\Customer\Domain\Model\User\User;
use App\Customer\Domain\Model\User\UserId;
use App\Customer\Domain\Repository\UserRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

final class ValidCustomerIdValidator extends ConstraintValidator
{
    public function __construct(private readonly UserRepository $userRepository)
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

        if ((int) $value > 0 && $this->userRepository->get(UserId::fromInt((int) $value)) instanceof User) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ value }}', (string) $value)
            ->addViolation();
    }
}
