<?php

declare(strict_types=1);

namespace App\Order\UI\Http\Validation;

use App\Catalog\Domain\Model\Product\Product;
use App\Catalog\Domain\Model\Product\ProductId;
use App\Catalog\Domain\Repository\ProductRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

final class ValidProductIdValidator extends ConstraintValidator
{
    public function __construct(private readonly ProductRepository $productRepository)
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

        if ((int) $value > 0 && $this->productRepository->get(ProductId::fromInt((int) $value)) instanceof Product) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ value }}', (string) $value)
            ->addViolation();
    }
}
