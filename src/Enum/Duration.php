<?php

namespace App\Enum;

use DateTime;

enum Duration: string
{
    case TODAY = 'today';
    case LAST_7 = 'last7';
    case LAST_30 = 'last30';
    case MTD = 'mtd';
    case DAY = 'day';
    case MONTH = 'month';

    public static function default(): self
    {
        return self::LAST_30;
    }

    public static function isValid(string $duration): bool
    {
        // check if the duration is a case->value in the list of cases
        return in_array($duration, array_column(self::cases(), 'value'), true);
    }

    public function getStartDate(bool $rebuildRange = false): string
    {
        $now = new DateTime();

        return match ($this) {
            self::TODAY => $now->format('Y-m-d'),
            self::LAST_7 => $now->modify('-7 days')->format('Y-m-d'),
            self::LAST_30 => $now->modify('-30 days')->format('Y-m-d'),
            self::MTD => $now->format('Y-m-01'),
            self::DAY => $rebuildRange ?
                $now->modify('-30 days')->format('Y-m-d')
                : $now->format('Y-m-d'),
            self::MONTH => $rebuildRange
                ? $now->modify('-12 months')->format('Y-m-01')
                : $now->format('Y-m-01'),
        };
    }

    public function getEndDate(): string
    {
        return (new DateTime('+1 day'))->format('Y-m-d');
    }

    public function getDateStringFormat(bool $rebuildRange = false): string
    {
        return match ($this) {
            self::TODAY, self::LAST_7, self::LAST_30, self::MTD => $this->getStartDate($rebuildRange),
            self::DAY => '%Y-%m-%d',
            self::MONTH => '%Y-%m-01',
        };
    }

    public function getChartLabelFormat(): string
    {
        return match ($this) {
            self::MONTH, self::MTD => 'M Y',
            default => 'd M',
        };
    }

    public function getChartGranularity(): string
    {
        return match ($this) {
            self::MONTH, self::MTD => '+1 month',
            default => '+1 day',
        };
    }

    public function getRangeStartDate(bool $rebuildRange = false): ?string
    {
        return (!$rebuildRange && in_array($this, [self::DAY, self::MONTH], true)) ?
            $this->getStartDate(false) : null;
    }
}