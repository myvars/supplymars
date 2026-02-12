<?php

namespace App\Audit\Domain\Model\StockChange;

enum HistoryDuration: string
{
    case LAST_7 = 'last7';
    case LAST_30 = 'last30';
    case LAST_90 = 'last90';
    case ALL = 'all';

    public static function default(): self
    {
        return self::LAST_30;
    }

    public function getSinceDate(): ?\DateTimeImmutable
    {
        return match ($this) {
            self::LAST_7 => new \DateTimeImmutable('-7 days'),
            self::LAST_30 => new \DateTimeImmutable('-30 days'),
            self::LAST_90 => new \DateTimeImmutable('-90 days'),
            self::ALL => null,
        };
    }
}
