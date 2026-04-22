<?php

declare(strict_types=1);

namespace App\Shared\UI\Twig\Components;

use App\Shared\UI\Twig\StatusColor;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class StatusBadge
{
    public string $status;

    public bool $showIcon = false;

    public bool $interactive = false;

    public function getColorClasses(): string
    {
        $color = StatusColor::resolve($this->status);

        return match ($color) {
            'green' => 'bg-green-50 text-green-700 inset-ring inset-ring-green-600/20 dark:bg-green-400/10 dark:text-green-400 dark:inset-ring-green-400/20',
            'blue' => 'bg-blue-50 text-blue-700 inset-ring inset-ring-blue-700/10 dark:bg-blue-400/10 dark:text-blue-400 dark:inset-ring-blue-400/20',
            'emerald' => 'bg-emerald-50 text-emerald-700 inset-ring inset-ring-emerald-600/20 dark:bg-emerald-400/10 dark:text-emerald-400 dark:inset-ring-emerald-400/20',
            'yellow' => 'bg-yellow-50 text-yellow-800 inset-ring inset-ring-yellow-600/20 dark:bg-yellow-400/10 dark:text-yellow-500 dark:inset-ring-yellow-400/20',
            'orange' => 'bg-orange-50 text-orange-700 inset-ring inset-ring-orange-600/20 dark:bg-orange-400/10 dark:text-orange-400 dark:inset-ring-orange-400/20',
            'purple' => 'bg-purple-50 text-purple-700 inset-ring inset-ring-purple-700/10 dark:bg-purple-400/10 dark:text-purple-400 dark:inset-ring-purple-400/20',
            'red' => 'bg-red-50 text-red-700 inset-ring inset-ring-red-600/10 dark:bg-red-400/10 dark:text-red-400 dark:inset-ring-red-400/20',
            default => 'bg-gray-50 text-gray-600 inset-ring inset-ring-gray-500/10 dark:bg-gray-400/10 dark:text-gray-400 dark:inset-ring-gray-400/20',
        };
    }
}
