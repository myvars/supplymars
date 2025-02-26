<?php

namespace App\Service\Dashboard\Report;

use App\DTO\ProductSalesReportDto;
use App\Enum\SalesDuration;
use App\Enum\SalesType;
use App\Repository\ProductSalesRepository;
use App\Repository\ProductSalesSummaryRepository;
use App\Service\Dashboard\BarChartBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\UX\Chartjs\Model\Chart;

#[AsTaggedItem('product-sales')]
final readonly class ProductSalesReport implements ReportInterface
{
    private ProductSalesReportDto $dto;

    public function __construct(
        private ProductSalesRepository $salesRepository,
        private ProductSalesSummaryRepository $summaryRepository,
        private BarChartBuilder $barChartBuilder,
    ) {
    }

    public function build(object $dto): ?array
    {
        if (!$dto instanceof ProductSalesReportDto) {
            throw new \InvalidArgumentException('Invalid DTO');
        }

        $this->dto = $dto;

        return [
            'type' => $this->dto->getSingleSalesType()['salesType']->value,
            'sales' => $this->getSales(),
            'summary' => $this->getSummary(),
            'productSalesChart' => $this->getProductSalesChart(),
        ];
    }

    private function getSales(): ?array
    {
        if (null !== $this->dto->getProductId()) {
            return null;
        }

        $sales = $this->salesRepository->findProductSalesByDto($this->dto);

        return [] === $sales ? null : $sales;
    }

    private function getSummary(): ?array
    {
        $singleSalesType = $this->dto->getSingleSalesType();
        if (null === $singleSalesType) {
            return null;
        }

        $summary = $this->summaryRepository->findProductSalesSummary(
            $singleSalesType['salesTypeId'],
            $singleSalesType['salesType'],
            $this->dto->getDuration()
        );

        return $summary ?? [];
    }

    private function getProductSalesChart(): ?Chart
    {
        $singleSalesType = $this->dto->getSingleSalesType();
        if (null === $singleSalesType) {
            return null;
        }

        $salesData = $this->getSalesData($singleSalesType['salesTypeId'], $singleSalesType['salesType']);
        if ([] === $salesData) {
            return null;
        }

        return $this->barChartBuilder->create($salesData, $this->dto->getDuration(), $this->dto->getSort());
    }

    private function getSalesData(int $salesTypeId, SalesType $salesType): array
    {
        $salesRangeDuration = $this->barChartBuilder::getSalesRangeDuration($this->dto->getDuration());

        if (SalesType::PRODUCT === $salesType && SalesDuration::DAY === $salesRangeDuration) {
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
