<?php

declare(strict_types=1);

namespace App\Order\UI\Http\Validation;

use App\Catalog\Infrastructure\Persistence\Doctrine\ProductDoctrineRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

final class ValidProductIdValidator extends ConstraintValidator
{
    public function __construct(private readonly ProductDoctrineRepository $productRepository)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidProductId) {
            throw new \InvalidArgumentException('Expected instance of ' . ValidProductId::class);
        }

        if (!is_numeric($value) || 0 === (int) $value) {
            return;
        }

        if ($product = $this->productRepository->find($value)) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ value }}', (string) $value)
            ->addViolation();
    }
}
