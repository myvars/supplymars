<?php

declare(strict_types=1);

namespace App\Reporting\Domain\Metric;

interface SalesMetricInterface
{
    public static function default(): self;

    public function getValue(): string;

    public static function isValid(string $metric): bool;

    public function getDataLabel(): string;

    /**
     * @return array<string, string>
     */
    public function getChartColors(): array;

    public function getYAxisType(): ?string;
}
