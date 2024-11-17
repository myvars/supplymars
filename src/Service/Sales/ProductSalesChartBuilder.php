<?php

namespace App\Service\Sales;

use App\DTO\ProductSalesFilterDto;
use App\Entity\ProductSalesSummary;
use App\Enum\Duration;
use DateInterval;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class ProductSalesChartBuilder
{

    private const CHART_TYPE = Chart::TYPE_BAR;

    private const CHART_COLOR = '#991b1b90';

    private const CHART_BORDER_COLOR = '#991b1b';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ChartBuilderInterface  $chartBuilder
    ) {
    }

    public function build(ProductSalesFilterDto $dto): ?Chart
    {
        $salesData = $this->getSalesRangeData($dto);
        if ($salesData === null) {
            return null;
        }

        $dataLabel = str_replace('sales', '', $dto->getSort());

        return $this->buildChart($salesData, $dataLabel);
    }

    private function buildChart(array $data, string $dataLabel): Chart
    {
        return $this->chartBuilder
            ->createChart(self::CHART_TYPE)
            ->setData([
                'labels' => array_keys($data),
                'datasets' => [
                    [
                        'label' => ucfirst($dataLabel),
                        'data' => array_values($data),
                        'backgroundColor' => self::CHART_COLOR,
                        'borderColor' => self::CHART_BORDER_COLOR,
                        'borderWidth' => 1,
                        'borderRadius' => [
                            'topLeft' => 3,
                            'topRight' => 3,
                        ],
                    ],
                ],
            ])
            ->setOptions([
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
                        ],
                    ],
                ],
                'layout' => [
                    'padding' => 20
                ],
            ]);
    }

    private function getSalesRangeData(ProductSalesFilterDto $dto): ?array
    {
        $singleSalesType = $dto->getSingleSalesType();
        if ($singleSalesType === null) {
            return null;
        }

        if ($dto->getDuration()->value === 'mtd') {
            $rangeDuration = Duration::MONTH;
            $startDate = Duration::MONTH->getStartDate(true);
        } else {
            $rangeDuration = Duration::DAY;
            $startDate = $dto->getDuration()->getStartDate();
        }

        $dateRange = $this->generateDateRange(
            new \DateTimeImmutable($startDate),
            new \DateTimeImmutable(),
            $dto->getDuration()->getChartLabelFormat(),
            $dto->getDuration()->getChartGranularity()
        );

        $salesData = $this->entityManager->getRepository(ProductSalesSummary::class)->findProductSalesSummaryRange(
            $singleSalesType['salesTypeId'],
            $singleSalesType['salesType'],
            $rangeDuration->value,
            $startDate
        );

        return $this->mergeSalesData(
            $dateRange,
            $salesData,
            $dto->getDuration()->getChartLabelFormat(),
            $dto->getSort()
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

        $period = new \DatePeriod($startDate, DateInterval::createFromDateString($granularity), $endDate);
        foreach ($period as $date) {
            $dateRange[$date->format($labelFormat)] = 0;
        }

        return $dateRange ?? [];
    }


    private function mergeSalesData(array $dateRange, array $salesData, string $labelFormat, string $metric): array
    {
        foreach ($salesData as $entry) {
            $salesDataByDate[$entry['salesDate']->format($labelFormat)] = $entry[$metric];
        }

        foreach (array_keys($dateRange) as $date) {
            $dateRange[$date] = $salesDataByDate[$date] ?? 0;
        }

        return $dateRange;
    }
}