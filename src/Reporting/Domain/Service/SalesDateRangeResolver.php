<?php

namespace App\Reporting\Domain\Service;

use App\Reporting\Domain\Metric\SalesDuration;

final readonly class SalesDateRangeResolver
{
    public function getRangeDuration(SalesDuration $salesDuration): SalesDuration
    {
        if (SalesDuration::MTD === $salesDuration) {
            return SalesDuration::MONTH;
        }

        return SalesDuration::DAY;
    }

    public function getRangeStartDate(SalesDuration $salesDuration): string
    {
        if (SalesDuration::MTD === $salesDuration) {
            return SalesDuration::MONTH->getStartDate(true);
        }

        return $salesDuration->getStartDate();
    }

    /**
     * @return array<string, int>
     */
    public function generateDateRange(
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
        string $labelFormat,
        string $granularity,
    ): array {
        if ($startDate > $endDate) {
            throw new \InvalidArgumentException('Start date must be less than or equal to end date.');
        }

        $dateRange = [];
        $interval = \DateInterval::createFromDateString($granularity);
        \assert($interval instanceof \DateInterval);
        $period = new \DatePeriod($startDate, $interval, $endDate);
        foreach ($period as $date) {
            $dateRange[$date->format($labelFormat)] = 0;
        }

        return $dateRange;
    }
}
