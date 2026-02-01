<?php

namespace App\Reporting\Application\Handler\Report;

use App\Reporting\Application\Report\CustomerGeographicReportCriteria;
use App\Reporting\Infrastructure\Persistence\Doctrine\CustomerGeographicSummaryDoctrineRepository;
use App\Reporting\UI\Http\Chart\CustomerCityColorProvider;
use App\Reporting\UI\Http\Chart\CustomerDoughnutChartBuilder;
use App\Shared\Application\Result;

final readonly class CustomerGeographicReportHandler
{
    public function __construct(
        private CustomerGeographicSummaryDoctrineRepository $geoRepository,
        private CustomerDoughnutChartBuilder $chartBuilder,
        private CustomerCityColorProvider $cityColorProvider,
    ) {
    }

    public function __invoke(CustomerGeographicReportCriteria $criteria): Result
    {
        $geoChart = null;

        $duration = $criteria->getDuration();
        $geoData = $this->geoRepository->findGeographicSummary($duration);

        // Summary KPIs
        $summary = $this->calculateSummary($geoData);

        if ($geoData !== []) {
            $geoChart = $this->chartBuilder->create(
                $geoData,
                'city',
                'orderValue',
                $this->cityColorProvider,
                'currency',
            );
        }

        return Result::ok('Report created', [
            'summary' => $summary,
            'geoChart' => $geoChart,
            'geoData' => $geoData,
        ]);
    }

    /**
     * @param array<int, array<string, mixed>> $geoData
     *
     * @return array<string, mixed>
     */
    private function calculateSummary(array $geoData): array
    {
        if ($geoData === []) {
            return [
                'topCity' => '-',
                'cityCount' => 0,
                'avgRevenuePerCity' => '0.00',
            ];
        }

        $totalRevenue = array_sum(array_map(fn (array $row): float => (float) $row['orderValue'], $geoData));
        $cityCount = count($geoData);

        return [
            'topCity' => $geoData[0]['city'] ?? '-',
            'cityCount' => $cityCount,
            'avgRevenuePerCity' => $cityCount > 0 ? number_format($totalRevenue / $cityCount, 2, '.', '') : '0.00',
        ];
    }
}
