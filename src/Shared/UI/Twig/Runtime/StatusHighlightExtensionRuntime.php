<?php

namespace App\Shared\UI\Twig\Runtime;

use App\Shared\UI\Twig\StatusColor;
use Twig\Extension\RuntimeExtensionInterface;

class StatusHighlightExtensionRuntime implements RuntimeExtensionInterface
{
    public function statusHighlight(string $status): string
    {
        $color = StatusColor::resolve($status);

        return match ($color) {
            'green' => 'border-l-green-500 dark:border-l-green-400',
            'blue' => 'border-l-blue-500 dark:border-l-blue-400',
            'emerald' => 'border-l-emerald-500 dark:border-l-emerald-400',
            'yellow' => 'border-l-yellow-500 dark:border-l-yellow-400',
            'orange' => 'border-l-orange-500 dark:border-l-orange-400',
            'purple' => 'border-l-purple-500 dark:border-l-purple-400',
            'red' => 'border-l-red-500 dark:border-l-red-400',
            default => 'border-l-gray-400 dark:border-l-gray-500',
        };
    }
}
