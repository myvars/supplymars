<?php

namespace App\Purchasing\UI\Http\Validation;

use App\Catalog\Domain\Model\Product\Product;
use App\Catalog\Domain\Model\Product\ProductId;
use App\Catalog\Domain\Repository\ProductRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

final class ValidProductIdValidator extends ConstraintValidator
{
    public function __construct(private readonly ProductRepository $products)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidProductId) {
            throw new \InvalidArgumentException('Expected instance of ' . ValidProductId::class);
        }

        if ($value === null || $value === '') {
            return;
        }

        if (is_int($value) && $value > 0) {
            $product = $this->products->get(ProductId::fromInt($value));
            if ($product instanceof Product) {
                return;
            }
        }

        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ value }}', $value)
            ->addViolation();
    }
}
