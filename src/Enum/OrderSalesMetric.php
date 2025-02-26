<?php

namespace App\Enum;

enum OrderSalesMetric: string implements SalesMetricInterface
{
    case COUNT = 'orderCount';
    case VALUE = 'orderValue';
    case AOV = 'averageOrderValue';

    public static function default(): self
    {
        return self::COUNT;
    }

    public static function isValid(string $metric): bool
    {
        // check if the duration is a case->value in the list of cases
        return in_array($metric, array_column(self::cases(), 'value'), true);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getDataLabel(): string
    {
        return match ($this) {
            self::COUNT => 'Count',
            self::VALUE => 'Value',
            self::AOV => 'AOV',
        };
    }

    public function getChartColors(): array
    {
        return match ($this) {
            self::COUNT => ['color' => '#991b1b90', 'borderColor' => '#991b1b'],
            self::VALUE => ['color' => '#04785790', 'borderColor' => '#047857'],
            self::AOV => ['color' => '#0e749090', 'borderColor' => '#0e7490'],
        };
    }

    public function getYAxisType(): ?string
    {
        return match ($this) {
            self::VALUE, self::AOV => 'currency',
            default => null,
        };
    }
}
