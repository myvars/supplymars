<?php

namespace App\Reporting\UI\Http\Chart;

use App\Reporting\Domain\Metric\SalesDuration;
use App\Reporting\Domain\Metric\SalesMetricInterface;
use App\Reporting\Domain\Service\SalesDateRangeResolver;
use Psr\Clock\ClockInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

final readonly class ProductSalesChartBuilder
{
    private const string CHART_TYPE = Chart::TYPE_BAR;

    public function __construct(
        private ChartBuilderInterface $chartBuilder,
        private SalesDateRangeResolver $dateRangeResolver,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @param array<int, array<string, mixed>> $salesData
     */
    public function create(array $salesData, SalesMetricInterface $salesMetric, ?SalesDuration $salesDuration = null): Chart
    {
        if (!$salesDuration instanceof SalesDuration) {
            throw new \InvalidArgumentException('SalesDuration is required for bar charts.');
        }

        $startDate = new \DateTimeImmutable($this->dateRangeResolver->getRangeStartDate($salesDuration));
        $endDate = $this->clock->now();
        $granularity = $salesDuration->getChartGranularity();

        $dateRange = $this->dateRangeResolver->generateDateRange(
            $startDate,
            $endDate,
            $salesDuration->getChartLabelFormat(),
            $granularity
        );

        $linkParams = $this->generateLinkParams($startDate, $endDate, $granularity);

        $chartData = $this->mergeSalesData(
            $dateRange,
            $salesData,
            $salesDuration->getChartLabelFormat(),
            $salesMetric->getValue()
        );

        return $this->buildChart($chartData, $salesMetric, $linkParams);
    }

    /**
     * @return array<int, string>
     */
    private function generateLinkParams(\DateTimeImmutable $startDate, \DateTimeImmutable $endDate, string $granularity): array
    {
        $params = [];
        $interval = \DateInterval::createFromDateString($granularity);
        \assert($interval instanceof \DateInterval);
        $period = new \DatePeriod($startDate, $interval, $endDate);
        foreach ($period as $date) {
            $start = $date->format('Y-m-d');
            $end = $date->modify($granularity)->format('Y-m-d');
            $params[] = 'startDate=' . $start . '&endDate=' . $end;
        }

        return $params;
    }

    /**
     * @param array<string, int|float> $data
     * @param array<int, string>       $linkParams
     */
    private function buildChart(array $data, SalesMetricInterface $salesMetric, array $linkParams = []): Chart
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
                'linkParams' => $linkParams,
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

    /**
     * @param array<string, int>               $dateRange
     * @param array<int, array<string, mixed>> $salesData
     *
     * @return array<string, int|float>
     */
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
}
