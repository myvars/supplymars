<?php

namespace App\Service\Sales;

use App\Enum\SalesDuration;
use App\Enum\SalesMetric;
use App\Enum\SalesType;
use App\Repository\ProductSalesRepository;
use App\Repository\ProductSalesSummaryRepository;
use DateInterval;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class ProductSalesChartBuilder
{
    private const CHART_TYPE = Chart::TYPE_BAR;

    public function __construct(
        private readonly ProductSalesRepository $productSalesRepository,
        private readonly ProductSalesSummaryRepository $productSalesSummaryRepository,
        private readonly ChartBuilderInterface  $chartBuilder
    ) {
    }

    public function build(
        int $salesTypeId,
        SalesType $salesType,
        SalesDuration $salesDuration,
        SalesMetric $salesMetric
    ): ?Chart {
        $salesData = $this->getSalesData($salesTypeId, $salesType, $salesDuration);
        if ($salesData === []) {
            return null;
        }

        $dateRange = $this->generateDateRange(
            new \DateTimeImmutable($this->getSalesRangeStartDate($salesDuration)),
            new \DateTimeImmutable(),
            $salesDuration->getChartLabelFormat(),
            $salesDuration->getChartGranularity()
        );

        $mergedData = $this->mergeSalesData(
            $dateRange,
            $salesData,
            $salesDuration->getChartLabelFormat(),
            $salesMetric->value
        );

        return $this->buildChart($mergedData, $salesMetric);
    }

    private function buildChart(array $data, SalesMetric $salesMetric): Chart
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
                            'currency' => $salesMetric->isCurrency(),
                            'percent' => $salesMetric->isPercentage(),
                        ],
                    ],
                ],
                'layout' => [
                    'padding' => 20
                ],
            ]);
    }

    public function getSalesData(
        int $salesTypeId,
        SalesType $salesType,
        SalesDuration $salesDuration
    ): array{
        $salesRangeDuration = $this->getSalesRangeDuration($salesDuration);

        if ($salesType === SalesType::PRODUCT && $salesRangeDuration === SalesDuration::DAY) {
            return $this->productSalesRepository->findProductSalesRange(
                $salesTypeId,
                $salesDuration->getStartDate(),
                $salesDuration->getEndDate()
            );
        }

        return $this->productSalesSummaryRepository->findProductSalesSummaryRange(
            $salesTypeId,
            $salesType,
            $salesRangeDuration,
            $this->getSalesRangeStartDate($salesDuration)
        );
    }

    private function generateDateRange(
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
        string $labelFormat,
        string $granularity
    ): array {
        if ($startDate > $endDate) {
            throw new \InvalidArgumentException('Start date must be less than or equal to end date.');
        }

        $dateRange = [];
        $period = new \DatePeriod($startDate, DateInterval::createFromDateString($granularity), $endDate);
        foreach ($period as $date) {
            $dateRange[$date->format($labelFormat)] = 0;
        }

        return $dateRange;
    }


    private function mergeSalesData(
        array $dateRange,
        array $salesData,
        string $labelFormat,
        string $salesMetric
    ): array {
        foreach ($salesData as $entry) {
            $salesDataByDate[$entry['salesDate']->format($labelFormat)] = $entry[$salesMetric];
        }

        foreach (array_keys($dateRange) as $date) {
            $dateRange[$date] = $salesDataByDate[$date] ?? 0;
        }

        return $dateRange;
    }

    private function getSalesRangeDuration(SalesDuration $salesDuration): SalesDuration
    {
        if ($salesDuration === SalesDuration::MTD) {
            return SalesDuration::MONTH;
        }

        return SalesDuration::DAY;
    }

    private function getSalesRangeStartDate(SalesDuration $salesDuration): string
    {
        if ($salesDuration === SalesDuration::MTD) {
            return SalesDuration::MONTH->getStartDate(true);
        }

        return $salesDuration->getStartDate();
    }
}