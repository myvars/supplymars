<?php

namespace App\Reporting\UI\Http\Dashboard\Chart;

use App\Reporting\Domain\Metric\SalesDuration;
use App\Reporting\Domain\Metric\SalesMetricInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

final class BarChartBuilder
{
    private const string CHART_TYPE = Chart::TYPE_BAR;

    public function __construct(private readonly ChartBuilderInterface $chartBuilder)
    {
    }

    public function create(array $salesData, SalesDuration $salesDuration, SalesMetricInterface $salesMetric): Chart
    {
        $dateRange = $this->generateDateRange(
            new \DateTimeImmutable(static::getSalesRangeStartDate($salesDuration)),
            new \DateTimeImmutable(),
            $salesDuration->getChartLabelFormat(),
            $salesDuration->getChartGranularity()
        );

        $chartData = $this->mergeSalesData(
            $dateRange,
            $salesData,
            $salesDuration->getChartLabelFormat(),
            $salesMetric->getValue()
        );

        return $this->buildChart($chartData, $salesMetric);
    }

    private function buildChart(array $data, SalesMetricInterface $salesMetric): Chart
    {
        return $this->chartBuilder
            ->createChart(self::CHART_TYPE)
            ->setData([
                'labels' => array_keys($data),
                'datasets' => [
                    [
                        'label' => ucfirst($salesMetric->getDataLabel()),
                        'data' => array_values($data),
                        'backgroundColor' => $salesMetric->getChartColors()['color'],
                        'borderColor' => $salesMetric->getChartColors()['borderColor'],
                        'borderWidth' => 1,
                        'borderRadius' => [
                            'topLeft' => 3,
                            'topRight' => 3,
                        ],
                    ],
                ],
            ])
            ->setOptions([
                'maintainAspectRatio' => false,
                'scales' => [
                    'x' => [
                        'grid' => [
                            'color' => '#ffffff10',
                        ],
                    ],
                    'y' => [
                        'beginAtZero' => true,
                        'grid' => [
                            'color' => '#ffffff10',
                            'axisType' => $salesMetric->getYAxisType(),
                        ],
                    ],
                ],
                'layout' => [
                    'padding' => 20,
                ],
            ]);
    }

    private function generateDateRange(
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
        string $labelFormat,
        string $granularity,
    ): array {
        if ($startDate > $endDate) {
            throw new \InvalidArgumentException('Start date must be less than or equal to end date.');
        }

        $dateRange = [];
        $period = new \DatePeriod($startDate, \DateInterval::createFromDateString($granularity), $endDate);
        foreach ($period as $date) {
            $dateRange[$date->format($labelFormat)] = 0;
        }

        return $dateRange;
    }

    private function mergeSalesData(
        array $dateRange,
        array $salesData,
        string $labelFormat,
        string $salesMetric,
    ): array {
        foreach ($salesData as $entry) {
            $salesDataByDate[$entry['salesDate']->format($labelFormat)] = $entry[$salesMetric];
        }

        foreach (array_keys($dateRange) as $date) {
            $dateRange[$date] = $salesDataByDate[$date] ?? 0;
        }

        return $dateRange;
    }

    public static function getSalesRangeDuration(SalesDuration $salesDuration): SalesDuration
    {
        if (SalesDuration::MTD === $salesDuration) {
            return SalesDuration::MONTH;
        }

        return SalesDuration::DAY;
    }

    public static function getSalesRangeStartDate(SalesDuration $salesDuration): string
    {
        if (SalesDuration::MTD === $salesDuration) {
            return SalesDuration::MONTH->getStartDate(true);
        }

        return $salesDuration->getStartDate();
    }
}
