<?php

declare(strict_types=1);

namespace App\Reporting\UI\Http\Chart;

final readonly class CustomerCityColorProvider implements ChartColorProviderInterface
{
    private const array COLORS = [
        '#3b82f6', '#ef4444', '#10b981', '#f59e0b', '#8b5cf6',
        '#ec4899', '#06b6d4', '#84cc16', '#f97316', '#6366f1',
        '#14b8a6', '#e11d48', '#a855f7', '#0ea5e9', '#eab308',
    ];

    public function getColor(string|int $key): string
    {
        $index = abs(crc32((string) $key)) % count(self::COLORS);

        return self::COLORS[$index];
    }

    public function getSortOrder(string|int $key): ?int
    {
        return null;
    }
}
