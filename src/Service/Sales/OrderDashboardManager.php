<?php

namespace App\Service\Sales;

use App\DTO\OrderSalesDashboardDto;
use App\Repository\CustomerOrderRepository;
use App\Repository\OrderSalesSummaryRepository;
use DateTime;
use Symfony\UX\Chartjs\Model\Chart;

class OrderDashboardManager
{
    private readonly OrderSalesDashboardDto $dto;

    public function __construct(
        private readonly CustomerOrderRepository $orderRepository,
        private readonly OrderSalesSummaryRepository $summaryRepository,
        private readonly BarChartBuilder $barChartBuilder,
        private readonly DoughnutChartBuilder $doughnutChartBuilder
    ) {
    }

    public function createFromDto(OrderSalesDashboardDto $dto): void
    {
        $this->dto = $dto;
    }

    public function getSummary(): ?array
    {
        $summary = $this->summaryRepository->findOrderSalesSummary($this->dto->getDuration());

        return $summary ?? [];
    }

    public function getOrderSalesChart(): ?Chart
    {
        $salesData = $this->summaryRepository->findOrderSalesSummaryRange(
            $this->barChartBuilder::getSalesRangeDuration($this->dto->getDuration()),
            $this->barChartBuilder::getSalesRangeStartDate($this->dto->getDuration())
        );
        if ($salesData === []) {
            return null;
        }

        return $this->barChartBuilder->create($salesData, $this->dto->getDuration(), $this->dto->getSort());
    }

    public function getOrderProgressChart(): ?Chart
    {
        $salesData = $this->orderRepository->calculateOrderSalesByStatus(
            Datetime::createFromFormat('Y-m-d', $this->dto->getDuration()->getStartDate()),
            DateTime::createFromFormat('Y-m-d', $this->dto->getDuration()->getEndDate()),
        );
        if ($salesData === []) {
            return null;
        }

        return $this->doughnutChartBuilder->create($salesData, $this->dto->getDuration(), $this->dto->getSort());
    }
}