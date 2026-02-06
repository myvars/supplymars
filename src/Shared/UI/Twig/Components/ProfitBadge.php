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
            ? 'bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400'
            : 'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-400';
    }
}
