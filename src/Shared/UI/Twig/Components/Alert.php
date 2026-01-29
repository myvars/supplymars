<?php

namespace App\Shared\UI\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class Alert
{
    public string $type = 'info';

    public function getColorClasses(): string
    {
        return match ($this->type) {
            'danger' => 'border-red-200 bg-red-50 text-red-800 dark:border-red-800/50 dark:bg-red-900/20 dark:text-red-400',
            'warning' => 'border-yellow-200 bg-yellow-50 text-yellow-800 dark:border-yellow-800/50 dark:bg-yellow-900/20 dark:text-yellow-400',
            'success' => 'border-green-200 bg-green-50 text-green-800 dark:border-green-800/50 dark:bg-green-900/20 dark:text-green-400',
            default => 'border-blue-200 bg-blue-50 text-blue-800 dark:border-blue-800/50 dark:bg-blue-900/20 dark:text-blue-400',
        };
    }

    public function getIconName(): string
    {
        return match ($this->type) {
            'danger' => 'bi:exclamation-circle',
            'warning' => 'bi:exclamation-triangle',
            'success' => 'bi:check-circle',
            default => 'bi:info-circle',
        };
    }
}
