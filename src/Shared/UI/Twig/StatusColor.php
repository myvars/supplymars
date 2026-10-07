<?php

declare(strict_types=1);

namespace App\Shared\UI\Twig;

final class StatusColor
{
    public static function resolve(string $status): string
    {
        return match (strtoupper($status)) {
            'DELIVERED', 'ACTIVE', 'VERIFIED', 'PUBLISHED' => 'green',
            'SHIPPED', 'OPEN', 'CUSTOMER', 'NEW' => 'blue',
            'REPLIED' => 'yellow',
            'ACCEPTED', 'STAFF', 'RETURNING' => 'emerald',
            'PROCESSING' => 'yellow',
            'REJECTED' => 'orange',
            'REFUNDED', 'LOYAL' => 'purple',
            'CANCELLED', 'INACTIVE', 'UNVERIFIED', 'ADMIN', 'LAPSED' => 'red',
            'CLOSED' => 'gray',
            default => 'gray',
        };
    }

    /** Tinted pill classes for a colour name from resolve(); used by StatusBadge and ProfitBadge. */
    public static function badgeClasses(string $color): string
    {
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

    /** Left-edge stripe classes for a colour name from resolve(); used by EntityCard's statusHighlight. */
    public static function stripeClasses(string $color): string
    {
        return 'border-l-4 ' . match ($color) {
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
