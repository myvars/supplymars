<?php

namespace App\Enum;

enum ProductSalesMetric: string implements SalesMetricInterface
{
    case QTY = 'salesQty';
    case COST = 'salesCost';
    case VALUE = 'salesValue';
    case PROFIT = 'salesProfit';
    case MARGIN = 'salesMargin';

    public static function default(): self
    {
        return self::QTY;
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
            self::QTY => 'Qty',
            self::COST => 'Cost',
            self::VALUE => 'Value',
            self::PROFIT => 'Profit',
            self::MARGIN => 'Margin',
        };
    }

    public function getChartColors(): array
    {
        return match ($this) {
            self::QTY => ['color' => '#991b1b90', 'borderColor' => '#991b1b'],
            self::COST => ['color' => '#b4530990', 'borderColor' => '#b45309'],
            self::VALUE => ['color' => '#04785790', 'borderColor' => '#047857'],
            self::PROFIT => ['color' => '#0e749090', 'borderColor' => '#0e7490'],
            self::MARGIN => ['color' => '#facc1590', 'borderColor' => '#facc15'],
        };
    }

    public function getYAxisType(): ?string
    {
        return match ($this) {
            self::COST, self::VALUE, self::PROFIT => 'currency',
            self::MARGIN => 'percentage',
            default => null,
        };
    }
}