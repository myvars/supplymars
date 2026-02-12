<?php

namespace App\Audit\UI\Http\Chart;

use App\Audit\Domain\Model\StockChange\SupplierStockChangeLog;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

final readonly class StockHistoryChartBuilder
{
    public function __construct(
        private ChartBuilderInterface $chartBuilder,
        private SupplierColorProvider $colorProvider,
    ) {
    }

    /**
     * @param array<int, SupplierStockChangeLog> $logs
     * @param array<int, string>                 $supplierNames   supplier product ID => supplier name
     * @param array<int, string>                 $supplierColours supplier product ID => colour scheme
     */
    public function createStockChart(array $logs, array $supplierNames, array $supplierColours): Chart
    {
        return $this->buildChart(
            $logs,
            $supplierNames,
            $supplierColours,
            'stock',
            ['stepped' => 'before'],
            'integer',
        );
    }

    /**
     * @param array<int, SupplierStockChangeLog> $logs
     * @param array<int, string>                 $supplierNames   supplier product ID => supplier name
     * @param array<int, string>                 $supplierColours supplier product ID => colour scheme
     */
    public function createCostChart(array $logs, array $supplierNames, array $supplierColours): Chart
    {
        return $this->buildChart(
            $logs,
            $supplierNames,
            $supplierColours,
            'cost',
            [],
            'currency',
        );
    }

    /**
     * @param array<int, SupplierStockChangeLog> $logs
     * @param array<int, string>                 $supplierNames
     * @param array<int, string>                 $supplierColours
     * @param array<string, mixed>               $datasetOptions
     */
    private function buildChart(
        array $logs,
        array $supplierNames,
        array $supplierColours,
        string $field,
        array $datasetOptions,
        string $yAxisType,
    ): Chart {
        $grouped = $this->groupBySupplier($logs);
        $dateRange = $this->generateDateRange($logs);
        $dateKeys = array_keys($dateRange);
        $displayLabels = array_values($dateRange);
        $datasets = [];

        foreach ($grouped as $supplierProductId => $supplierLogs) {
            $name = $supplierNames[$supplierProductId] ?? 'Unknown';
            $colourScheme = $supplierColours[$supplierProductId] ?? 'supplier1';
            $color = $this->colorProvider->getColor($colourScheme);
            $data = $this->carryForward($supplierLogs, $dateKeys, $field);

            $datasets[] = array_merge([
                'label' => $name,
                'data' => array_values($data),
                'borderColor' => $color,
                'backgroundColor' => $color,
                'fill' => false,
                'tension' => $field === 'cost' ? 0.3 : 0,
                'pointRadius' => 0,
                'pointHoverRadius' => 5,
                'pointHitRadius' => 10,
                'borderWidth' => 2,
            ], $datasetOptions);
        }

        $labelCount = count($displayLabels);

        return $this->chartBuilder
            ->createChart(Chart::TYPE_LINE)
            ->setData([
                'labels' => $displayLabels,
                'datasets' => $datasets,
            ])
            ->setOptions([
                'maintainAspectRatio' => false,
                'interaction' => [
                    'mode' => 'index',
                    'intersect' => false,
                ],
                'scales' => [
                    'x' => [
                        'grid' => ['color' => '#ffffff10'],
                        'ticks' => [
                            'maxRotation' => 0,
                            'autoSkip' => true,
                            'maxTicksLimit' => min($labelCount, 12),
                        ],
                    ],
                    'y' => [
                        'beginAtZero' => $field === 'stock',
                        'grid' => [
                            'color' => '#ffffff10',
                            'axisType' => $yAxisType,
                        ],
                    ],
                ],
                'plugins' => [
                    'legend' => [
                        'display' => true,
                        'position' => 'top',
                        'labels' => [
                            'usePointStyle' => true,
                            'pointStyle' => 'line',
                            'padding' => 20,
                        ],
                    ],
                ],
                'layout' => ['padding' => ['top' => 10, 'right' => 20, 'bottom' => 10, 'left' => 10]],
            ]);
    }

    /**
     * @param array<int, SupplierStockChangeLog> $logs
     *
     * @return array<int, array<int, SupplierStockChangeLog>>
     */
    private function groupBySupplier(array $logs): array
    {
        $grouped = [];
        foreach ($logs as $log) {
            $grouped[$log->getSupplierProductId()][] = $log;
        }

        return $grouped;
    }

    /**
     * Generates a date range keyed by Y-m-d with display labels.
     * Uses 'd M' for ranges within a single year, 'd M y' when spanning multiple years.
     *
     * @param array<int, SupplierStockChangeLog> $logs
     *
     * @return array<string, string> Y-m-d => display label
     */
    private function generateDateRange(array $logs): array
    {
        if ($logs === []) {
            return [];
        }

        $min = $logs[0]->getEventTimestamp();
        $max = $logs[0]->getEventTimestamp();

        foreach ($logs as $log) {
            $ts = $log->getEventTimestamp();
            if ($ts < $min) {
                $min = $ts;
            }

            if ($ts > $max) {
                $max = $ts;
            }
        }

        $multiYear = $min->format('Y') !== $max->format('Y');
        $labelFormat = $multiYear ? 'd M y' : 'd M';

        $range = [];
        $current = $min->setTime(0, 0);
        $end = $max->setTime(0, 0);

        while ($current <= $end) {
            $range[$current->format('Y-m-d')] = $current->format($labelFormat);
            $current = $current->modify('+1 day');
        }

        return $range;
    }

    /**
     * @param array<int, SupplierStockChangeLog> $logs
     * @param array<int, string>                 $dateKeys Y-m-d keys
     *
     * @return array<string, int|float|null>
     */
    private function carryForward(array $logs, array $dateKeys, string $field): array
    {
        $dailyValues = [];
        foreach ($logs as $log) {
            $day = $log->getEventTimestamp()->format('Y-m-d');
            $value = $field === 'stock' ? $log->getStock() : (float) $log->getCost();
            $dailyValues[$day] = $value;
        }

        $result = [];
        $lastValue = null;

        foreach ($dateKeys as $key) {
            if (isset($dailyValues[$key])) {
                $lastValue = $dailyValues[$key];
            }

            $result[$key] = $lastValue;
        }

        return $result;
    }
}
