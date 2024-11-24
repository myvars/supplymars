<?php

namespace App\Service\Sales;

use App\Enum\OrderStatus;
use App\Enum\SalesDuration;
use App\Enum\SalesMetricInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class DoughnutChartBuilder
{
    private const CHART_TYPE = Chart::TYPE_DOUGHNUT;

    public function __construct(private readonly ChartBuilderInterface $chartBuilder)
    {
    }

    public function create(array $salesData, SalesDuration $salesDuration, SalesMetricInterface $salesMetric): Chart
    {
        $chartData = $this->createChartData($salesData, $salesMetric->getValue());

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
                        'backgroundColor' => array_map(
                            fn($status): string => OrderStatus::from($status)->getChartColor(),
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

    private function createChartData(array $salesData, string $salesMetric): array
    {
        $chartData = [];
        foreach ($salesData as $entry) {
            $chartData[$entry['status']->value] = $entry[$salesMetric];
        }

        return $this->sortChartData($chartData);
    }

    private function sortChartData(array $chartData): array
    {
        $order = [];
        foreach (OrderStatus::cases() as $status) {
            $order[strtolower($status->value)] = $status->getLevel();
        }

        uksort($chartData, fn($a, $b): int => $order[strtolower((string) $a)] <=> $order[strtolower((string) $b)]);

        return $chartData;
    }
}