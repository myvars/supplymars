<?php

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
            'green' => 'bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400',
            'blue' => 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
            'emerald' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
            'yellow' => 'bg-yellow-50 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
            'orange' => 'bg-orange-50 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
            'purple' => 'bg-purple-50 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
            'red' => 'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-400',
            default => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
        };
    }
}
