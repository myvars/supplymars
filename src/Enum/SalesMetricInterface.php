<?php

namespace App\Enum;

interface SalesMetricInterface
{
    public static function default(): self;

    public function getValue(): string;

    public static function isValid(string $metric): bool;

    public function getDataLabel(): string;

    public function getChartColors(): array;

    public function getYAxisType(): ?string;
}
