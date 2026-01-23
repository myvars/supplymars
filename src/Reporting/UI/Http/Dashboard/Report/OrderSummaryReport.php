<?php

namespace App\Reporting\UI\Http\Dashboard\Report;

use App\Order\Infrastructure\Persistence\Doctrine\CustomerOrderDoctrineRepository;
use App\Reporting\Application\Report\OrderSummaryReportCriteria;
use App\Reporting\Domain\Metric\OrderSalesMetric;
use App\Reporting\Infrastructure\Persistence\Doctrine\OrderSalesSummaryDoctrineRepository;
use App\Reporting\UI\Http\Dashboard\Chart\BarChartBuilder;
use App\Reporting\UI\Http\Dashboard\Chart\DoughnutChartBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\UX\Chartjs\Model\Chart;

#[AsTaggedItem('order-summary', priority: 10)]
final class OrderSummaryReport implements ReportInterface
{
    private OrderSummaryReportCriteria $dto;

    public function __construct(
        private readonly CustomerOrderDoctrineRepository $orderRepository,
        private readonly OrderSalesSummaryDoctrineRepository $summaryRepository,
        private readonly BarChartBuilder $barChartBuilder,
        private readonly DoughnutChartBuilder $doughnutChartBuilder,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function build(object $dto): array
    {
        if (!$dto instanceof OrderSummaryReportCriteria) {
            throw new \InvalidArgumentException('Invalid DTO');
        }

        $this->dto = $dto;

        return [
            'summary' => $this->getSummary(),
            'orderSalesChart' => $this->getOrderSalesChart(),
            'orderProgressChart' => $this->getOrderProgressChart(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getSummary(): array
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

        return $this->barChartBuilder->create(
            $salesData,
            $this->dto->getDuration(),
            OrderSalesMetric::from($this->dto->getSort())
        );
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

        return $this->doughnutChartBuilder->create(
            $salesData,
            $this->dto->getDuration(),
            OrderSalesMetric::from($this->dto->getSort())
        );
    }
}
