<?php

namespace App\Reporting\Domain\Metric;

enum CustomerSalesMetric: string implements SalesMetricInterface
{
    case ACTIVE = 'activeCustomers';
    case NEW = 'newCustomers';
    case RETURNING = 'returningCustomers';

    public static function default(): self
    {
        return self::ACTIVE;
    }

    public static function isValid(string $metric): bool
    {
        return in_array($metric, array_column(self::cases(), 'value'), true);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getDataLabel(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active Customers',
            self::NEW => 'New Customers',
            self::RETURNING => 'Returning Customers',
        };
    }

    /**
     * @return array<string, string>
     */
    public function getChartColors(): array
    {
        return match ($this) {
            self::ACTIVE => ['color' => '#3b82f690', 'borderColor' => '#3b82f6'],
            self::NEW => ['color' => '#10b98190', 'borderColor' => '#10b981'],
            self::RETURNING => ['color' => '#8b5cf690', 'borderColor' => '#8b5cf6'],
        };
    }

    public function getYAxisType(): ?string
    {
        return null;
    }
}
