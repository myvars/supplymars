<?php

namespace App\Reporting\UI\Http\Chart;

use App\Reporting\Domain\Metric\CustomerSegment;

final readonly class CustomerSegmentColorProvider implements ChartColorProviderInterface
{
    public function getColor(string|int $key): string
    {
        $segment = CustomerSegment::tryFrom((string) $key);

        return $segment?->getChartColor() ?? '#6b7280';
    }

    public function getSortOrder(string|int $key): ?int
    {
        $segment = CustomerSegment::tryFrom((string) $key);

        return $segment?->getSortOrder();
    }
}
