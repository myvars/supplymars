<?php

namespace App\Reporting\Application\Handler\Report;

use App\Order\Infrastructure\Persistence\Doctrine\CustomerOrderDoctrineRepository;
use App\Reporting\Application\Report\CustomerInsightsReportCriteria;
use App\Reporting\Domain\Metric\CustomerSalesMetric;
use App\Reporting\Infrastructure\Persistence\Doctrine\CustomerSalesDoctrineRepository;
use App\Reporting\Infrastructure\Persistence\Doctrine\CustomerSalesSummaryDoctrineRepository;
use App\Reporting\UI\Http\Chart\ProductSalesChartBuilder;
use App\Shared\Application\Result;

final readonly class CustomerInsightsReportHandler
{
    public function __construct(
        private CustomerSalesSummaryDoctrineRepository $summaryRepository,
        private CustomerSalesDoctrineRepository $salesRepository,
        private CustomerOrderDoctrineRepository $orderRepository,
        private ProductSalesChartBuilder $chartBuilder,
    ) {
    }

    public function __invoke(CustomerInsightsReportCriteria $criteria): Result
    {
        $customerChart = null;

        $duration = $criteria->getDuration();
        $summary = $this->summaryRepository->findCustomerSalesSummary($duration);

        $salesData = $this->salesRepository->findCustomerActivitySummaryRange(
            $duration->getStartDate(),
            $duration->getEndDate(),
        );

        if ($salesData !== []) {
            $metric = CustomerSalesMetric::isValid($criteria->getSort())
                ? CustomerSalesMetric::from($criteria->getSort())
                : CustomerSalesMetric::default();

            $customerChart = $this->chartBuilder->create(
                $salesData,
                $metric,
                $duration,
            );
        }

        $topCustomers = $this->orderRepository->findTopCustomersByRevenue(
            new \DateTime($duration->getStartDate()),
            new \DateTime($duration->getEndDate()),
        );

        return Result::ok('Report created', [
            'summary' => $summary,
            'customerChart' => $customerChart,
            'topCustomers' => $topCustomers,
        ]);
    }
}
