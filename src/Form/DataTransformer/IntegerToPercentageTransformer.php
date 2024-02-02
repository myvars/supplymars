<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class IntegerToPercentageTransformer implements DataTransformerInterface
{

    /**
     * Transforms an integer (value) to a percentage.
     *
     */
    public function transform(mixed $value): string
    {
        return $value / 100;
    }

    /**
     * Transforms a percentage (percent to an integer.
     *
     */
    public function reverseTransform(mixed $value): int
    {
        return $value * 100;
    }
}