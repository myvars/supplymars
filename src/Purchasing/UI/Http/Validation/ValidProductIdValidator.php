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

    public function validate($value, Constraint $constraint): void
    {
        /* @var ValidProductId $constraint */

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
