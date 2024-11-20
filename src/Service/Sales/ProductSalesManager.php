<?php

namespace App\Service\Sales;

use App\DTO\ProductSalesFilterDto;
use App\Repository\ProductSalesRepository;
use App\Repository\ProductSalesSummaryRepository;
use Symfony\UX\Chartjs\Model\Chart;

class ProductSalesManager
{
    private readonly ProductSalesFilterDto $dto;

    public function __construct(
        private readonly ProductSalesRepository $salesRepository,
        private readonly ProductSalesSummaryRepository $summaryRepository,
        private readonly ProductSalesChartBuilder $chartBuilder,
    ) {
    }

    public function createFromDto(ProductSalesFilterDto $dto): void
    {
        $this->dto = $dto;
    }

    public function getSales(): ?array
    {
        if ($this->dto->getProductId() !== null) {
            return null;
        }

        $sales = $this->salesRepository->findProductSalesBySalesDto($this->dto);

        return empty($sales) ? null : $sales;
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

    public function getChart(): ?Chart
    {
        $singleSalesType = $this->dto->getSingleSalesType();
        if ($singleSalesType === null) {
            return null;
        }

        return $this->chartBuilder->build(
            $singleSalesType['salesTypeId'],
            $singleSalesType['salesType'],
            $this->dto->getDuration(),
            $this->dto->getSort()
        );
    }
}