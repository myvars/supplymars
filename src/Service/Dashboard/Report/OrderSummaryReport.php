<?php

namespace App\Service\Dashboard\Report;

use App\DTO\OrderSummaryReportDto;
use App\Repository\CustomerOrderRepository;
use App\Repository\OrderSalesSummaryRepository;
use App\Service\Dashboard\BarChartBuilder;
use App\Service\Dashboard\DoughnutChartBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\UX\Chartjs\Model\Chart;

#[AsTaggedItem('order-summary', priority: 10)]
final readonly class OrderSummaryReport implements ReportInterface
{
    private OrderSummaryReportDto $dto;

    public function __construct(
        private CustomerOrderRepository $orderRepository,
        private OrderSalesSummaryRepository $summaryRepository,
        private BarChartBuilder $barChartBuilder,
        private DoughnutChartBuilder $doughnutChartBuilder,
    ) {
    }

    public function build(object $dto): ?array
    {
        if (!$dto instanceof OrderSummaryReportDto) {
            throw new \InvalidArgumentException('Invalid DTO');
        }

        $this->dto = $dto;

        return [
            'summary' => $this->getSummary(),
            'orderSalesChart' => $this->getOrderSalesChart(),
            'orderProgressChart' => $this->getOrderProgressChart(),
        ];
    }

    private function getSummary(): ?array
    {
        $summary = $this->summaryRepository->findOrderSalesSummary($this->dto->getDuration());

        return $summary ?? [];
    }

    private function getOrderSalesChart(): ?Chart
    {
        $salesData = $this->summaryRepository->findOrderSalesSummaryRange(
            $this->barChartBuilder::getSalesRangeDuration($this->dto->getDuration()),
            $this->barChartBuilder::getSalesRangeStartDate($this->dto->getDuration())
        );
        if ([] === $salesData) {
            return null;
        }

        return $this->barChartBuilder->create($salesData, $this->dto->getDuration(), $this->dto->getSort());
    }

    private function getOrderProgressChart(): ?Chart
    {
        $salesData = $this->orderRepository->findOrderSalesByStatus(
            new \DateTime($this->dto->getDuration()->getStartDate()),
            new \DateTime($this->dto->getDuration()->getEndDate()),
        );
        if ([] === $salesData) {
            return null;
        }

        return $this->doughnutChartBuilder->create($salesData, $this->dto->getDuration(), $this->dto->getSort());
    }
}
