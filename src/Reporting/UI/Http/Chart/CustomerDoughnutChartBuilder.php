<?php

declare(strict_types=1);

namespace App\Reporting\UI\Http\Chart;

use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

final readonly class CustomerDoughnutChartBuilder
{
    private const string CHART_TYPE = Chart::TYPE_DOUGHNUT;

    public function __construct(
        private ChartBuilderInterface $chartBuilder,
    ) {
    }

    /**
     * @param array<int, array<string, mixed>> $salesData
     */
    public function create(
        array $salesData,
        string $labelKey,
        string $valueKey,
        ChartColorProviderInterface $colorProvider,
        ?string $yAxisType = null,
    ): Chart {
        $chartData = [];
        foreach ($salesData as $entry) {
            $label = $entry[$labelKey] instanceof \BackedEnum ? $entry[$labelKey]->value : (string) $entry[$labelKey];
            $chartData[$label] = (float) $entry[$valueKey];
        }

        return $this->buildChart($chartData, $colorProvider, $valueKey, $yAxisType);
    }

    /**
     * @param array<int|string, float> $data
     */
    private function buildChart(
        array $data,
        ChartColorProviderInterface $colorProvider,
        string $dataLabel,
        ?string $yAxisType,
    ): Chart {
        return $this->chartBuilder
            ->createChart(self::CHART_TYPE)
            ->setData([
                'labels' => array_keys($data),
                'datasets' => [
                    [
                        'label' => ucfirst($dataLabel),
                        'data' => array_values($data),
                        'backgroundColor' => array_map(
                            $colorProvider->getColor(...),
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
                        'axisType' => $yAxisType,
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
}
