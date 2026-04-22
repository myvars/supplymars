<?php

declare(strict_types=1);

namespace App\Reporting\Application\Handler\Report;

use App\Reporting\Application\Report\CustomerSegmentReportCriteria;
use App\Reporting\Domain\Metric\CustomerSegment;
use App\Reporting\Infrastructure\Persistence\Doctrine\CustomerSegmentSummaryDoctrineRepository;
use App\Reporting\UI\Http\Chart\CustomerDoughnutChartBuilder;
use App\Reporting\UI\Http\Chart\CustomerSegmentColorProvider;
use App\Shared\Application\Result;

final readonly class CustomerSegmentReportHandler
{
    public function __construct(
        private CustomerSegmentSummaryDoctrineRepository $segmentRepository,
        private CustomerDoughnutChartBuilder $chartBuilder,
        private CustomerSegmentColorProvider $segmentColorProvider,
    ) {
    }

    public function __invoke(CustomerSegmentReportCriteria $criteria): Result
    {
        $segmentChart = null;

        $duration = $criteria->getDuration();
        $segmentData = $this->segmentRepository->findSegmentSummary($duration);

        // Summary KPIs - counts by segment
        $summary = $this->calculateSummary($segmentData);

        if ($segmentData !== []) {
            $segmentChart = $this->chartBuilder->create(
                $segmentData,
                'segment',
                'orderValue',
                $this->segmentColorProvider,
                'currency',
            );
        }

        return Result::ok('Report created', [
            'summary' => $summary,
            'segmentChart' => $segmentChart,
            'segmentData' => $segmentData,
        ]);
    }

    /**
     * @param array<int, array<string, mixed>> $segmentData
     *
     * @return array<string, int>
     */
    private function calculateSummary(array $segmentData): array
    {
        $counts = [
            'new' => 0,
            'returning' => 0,
            'loyal' => 0,
            'lapsed' => 0,
        ];

        foreach ($segmentData as $row) {
            $segment = $row['segment'] instanceof CustomerSegment ? $row['segment']->value : (string) $row['segment'];
            if (isset($counts[$segment])) {
                $counts[$segment] = (int) $row['customerCount'];
            }
        }

        return $counts;
    }
}
