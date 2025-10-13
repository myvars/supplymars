<?php

namespace App\Order\UI\Http\Validation;

use App\Catalog\Infrastructure\Persistence\Doctrine\ProductDoctrineRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

final class ValidProductIdValidator extends ConstraintValidator
{
    public function __construct(private readonly ProductDoctrineRepository $productRepository)
    {
    }

    public function validate($value, Constraint $constraint): void
    {
        /* @var ValidProductId $constraint */

        if (!is_numeric($value) || 0 === (int) $value) {
            return;
        }

        if ($product = $this->productRepository->find($value)) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ value }}', $value)
            ->addViolation();
    }
}
