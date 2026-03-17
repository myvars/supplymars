<?php

namespace App\Shared\UI\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class ProfitBadge
{
    public string|float $value = 0;

    public function isPositive(): bool
    {
        return (float) $this->value >= 0;
    }

    public function getFormattedValue(): float
    {
        return abs((float) $this->value);
    }

    public function getColorClasses(): string
    {
        return $this->isPositive()
            ? 'text-green-600 dark:text-green-400'
            : 'text-red-600 dark:text-red-400';
    }
}
