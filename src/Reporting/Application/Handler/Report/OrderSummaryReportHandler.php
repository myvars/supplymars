<?php

namespace App\Reporting\Application\Handler\Report;

use App\Order\Infrastructure\Persistence\Doctrine\CustomerOrderDoctrineRepository;
use App\Reporting\Application\Report\OrderSummaryReportCriteria;
use App\Reporting\Domain\Metric\OrderSalesMetric;
use App\Reporting\Domain\Service\SalesDateRangeResolver;
use App\Reporting\Infrastructure\Persistence\Doctrine\OrderSalesSummaryDoctrineRepository;
use App\Reporting\UI\Http\Chart\OrderProgressChartBuilder;
use App\Reporting\UI\Http\Chart\ProductSalesChartBuilder;
use App\Shared\Application\Result;

final readonly class OrderSummaryReportHandler
{
    public function __construct(
        private CustomerOrderDoctrineRepository $orderRepository,
        private OrderSalesSummaryDoctrineRepository $summaryRepository,
        private ProductSalesChartBuilder $productSalesChartBuilder,
        private OrderProgressChartBuilder $orderProgressChartBuilder,
        private SalesDateRangeResolver $dateRangeResolver,
    ) {
    }

    public function __invoke(OrderSummaryReportCriteria $criteria): Result
    {
        $orderSalesChart = null;
        $orderProgressChart = null;

        $duration = $criteria->getDuration();
        $summary = $this->summaryRepository->findOrderSalesSummary($duration) ?? [];

        $salesData = $this->summaryRepository->findOrderSalesSummaryRange(
            $this->dateRangeResolver->getRangeDuration($duration),
            $this->dateRangeResolver->getRangeStartDate($duration),
        );

        if ($salesData !== []) {
            $orderSalesChart = $this->productSalesChartBuilder->create(
                $salesData,
                OrderSalesMetric::from($criteria->getSort()),
                $duration
            );
        }

        $salesData = $this->orderRepository->findOrderSalesByStatus(
            new \DateTime($duration->getStartDate()),
            new \DateTime($duration->getEndDate()),
        );

        if ($salesData !== []) {
            $orderProgressChart = $this->orderProgressChartBuilder->create(
                $salesData,
                OrderSalesMetric::from($criteria->getSort())
            );
        }

        return Result::ok('Report created', [
            'summary' => $summary,
            'orderSalesChart' => $orderSalesChart,
            'orderProgressChart' => $orderProgressChart,
            'startDate' => $duration->getStartDate(),
            'endDate' => $duration->getEndDate(),
        ]);
    }
}
