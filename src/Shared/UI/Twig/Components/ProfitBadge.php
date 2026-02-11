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
            ? 'bg-green-50 text-green-700 inset-ring inset-ring-green-600/20 dark:bg-green-400/10 dark:text-green-400 dark:inset-ring-green-400/20'
            : 'bg-red-50 text-red-700 inset-ring inset-ring-red-600/10 dark:bg-red-400/10 dark:text-red-400 dark:inset-ring-red-400/20';
    }
}
