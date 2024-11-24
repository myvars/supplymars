<?php

namespace App\Service\Sales;

use App\DTO\ProductSalesDashboardDto;
use App\Enum\SalesDuration;
use App\Enum\SalesType;
use App\Repository\ProductSalesRepository;
use App\Repository\ProductSalesSummaryRepository;
use Symfony\UX\Chartjs\Model\Chart;

class ProductSalesDashboardManager
{
    private readonly ProductSalesDashboardDto $dto;

    public function __construct(
        private readonly ProductSalesRepository $salesRepository,
        private readonly ProductSalesSummaryRepository $summaryRepository,
        private readonly BarChartBuilder $barChartBuilder,
    ) {
    }

    public function createFromDto(ProductSalesDashboardDto $dto): void
    {
        $this->dto = $dto;
    }

    public function getSales(): ?array
    {
        if ($this->dto->getProductId() !== null) {
            return null;
        }

        $sales = $this->salesRepository->findProductSalesByDto($this->dto);

        return $sales === [] ? null : $sales;
    }

    public function getSummary(): ?array
    {
        $singleSalesType = $this->dto->getSingleSalesType();
        if ($singleSalesType === null) {
            return null;
        }

        $summary = $this->summaryRepository->findProductSalesSummary(
            $singleSalesType['salesTypeId'],
            $singleSalesType['salesType'],
            $this->dto->getDuration()
        );

        return $summary ?? [];
    }

    public function getProductSalesChart(): ?Chart
    {
        $singleSalesType = $this->dto->getSingleSalesType();
        if ($singleSalesType === null) {
            return null;
        }

        $salesData = $this->getSalesData($singleSalesType['salesTypeId'], $singleSalesType['salesType']);
        if ($salesData === []) {
            return null;
        }

        return $this->barChartBuilder->create($salesData, $this->dto->getDuration(), $this->dto->getSort());
    }

    private function getSalesData(int $salesTypeId, SalesType $salesType): array
    {
        $salesRangeDuration = $this->barChartBuilder::getSalesRangeDuration($this->dto->getDuration());

        if ($salesType === SalesType::PRODUCT && $salesRangeDuration === SalesDuration::DAY) {
            return $this->salesRepository->findProductSalesRange(
                $salesTypeId,
                $this->dto->getDuration()->getStartDate(),
                $this->dto->getDuration()->getEndDate()
            );
        }

        return $this->summaryRepository->findProductSalesSummaryRange(
            $salesTypeId,
            $salesType,
            $salesRangeDuration,
            $this->barChartBuilder::getSalesRangeStartDate($this->dto->getDuration())
        );
    }
}