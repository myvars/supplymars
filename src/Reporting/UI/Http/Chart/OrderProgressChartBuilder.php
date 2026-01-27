<?php

namespace App\Reporting\UI\Http\Chart;

use App\Reporting\Domain\Metric\SalesMetricInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

final readonly class OrderProgressChartBuilder
{
    private const string CHART_TYPE = Chart::TYPE_DOUGHNUT;

    public function __construct(
        private ChartBuilderInterface $chartBuilder,
        private ChartColorProviderInterface $colorProvider,
    ) {
    }

    /**
     * @param array<int, array<string, mixed>> $salesData
     */
    public function create(
        array $salesData,
        SalesMetricInterface $salesMetric,
    ): Chart {
        $chartData = $this->createChartData($salesData, $salesMetric->getValue());

        return $this->buildChart($chartData, $salesMetric);
    }

    /**
     * @param array<string, int|float> $data
     */
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
                        'backgroundColor' => array_map(
                            $this->colorProvider->getColor(...),
                            array_keys($data)
                        ),
                        'borderWidth' => 1,
                        'borderColor' => '#6b7280',
                    ],
                ],
            ])
            ->setOptions([
                'maintainAspectRatio' => false,
                'layout' => [
                    'padding' => 20,
                    'borderWidth' => 1,
                ],
                'plugins' => [
                    'tooltip' => [
                        'axisType' => $salesMetric->getYAxisType(),
                    ],
                    'legend' => [
                        'position' => 'right',
                        'labels' => [
                            'padding' => 25,
                        ],
                    ],
                ],
            ]);
    }

    /**
     * @param array<int, array<string, mixed>> $salesData
     *
     * @return array<string, int|float>
     */
    private function createChartData(array $salesData, string $salesMetric): array
    {
        $chartData = [];
        foreach ($salesData as $entry) {
            $chartData[$entry['status']->value] = $entry[$salesMetric];
        }

        return $this->sortChartData($chartData);
    }

    /**
     * @param array<string, int|float> $chartData
     *
     * @return array<string, int|float>
     */
    private function sortChartData(array $chartData): array
    {
        uksort(
            $chartData,
            fn (string $a, string $b): int => $this->getSortOrder($a) <=> $this->getSortOrder($b)
        );

        return $chartData;
    }

    /**
     * @return int Sort order for the key
     */
    private function getSortOrder(string $key): int
    {
        return $this->colorProvider->getSortOrder($key) ?? 0;
    }
}
