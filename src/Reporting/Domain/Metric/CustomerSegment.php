<?php

declare(strict_types=1);

namespace App\Reporting\Domain\Metric;

enum CustomerSegment: string
{
    case NEW = 'new';
    case RETURNING = 'returning';
    case LOYAL = 'loyal';
    case LAPSED = 'lapsed';

    public function getLabel(): string
    {
        return match ($this) {
            self::NEW => 'New',
            self::RETURNING => 'Returning',
            self::LOYAL => 'Loyal',
            self::LAPSED => 'Lapsed',
        };
    }

    public function getChartColor(): string
    {
        return match ($this) {
            self::NEW => '#3b82f6',
            self::RETURNING => '#10b981',
            self::LOYAL => '#8b5cf6',
            self::LAPSED => '#ef4444',
        };
    }

    public function getSortOrder(): int
    {
        return match ($this) {
            self::NEW => 1,
            self::RETURNING => 2,
            self::LOYAL => 3,
            self::LAPSED => 4,
        };
    }
}
