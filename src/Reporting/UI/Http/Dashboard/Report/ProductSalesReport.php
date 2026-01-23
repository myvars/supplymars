<?php

namespace App\Reporting\UI\Http\Dashboard\Report;

use App\Reporting\Application\Report\ProductSalesReportCriteria;
use App\Reporting\Domain\Metric\ProductSalesMetric;
use App\Reporting\Domain\Metric\SalesDuration;
use App\Reporting\Domain\Metric\SalesType;
use App\Reporting\Infrastructure\Persistence\Doctrine\ProductSalesDoctrineRepository;
use App\Reporting\Infrastructure\Persistence\Doctrine\ProductSalesSummaryDoctrineRepository;
use App\Reporting\UI\Http\Dashboard\Chart\BarChartBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\UX\Chartjs\Model\Chart;

#[AsTaggedItem('product-sales')]
final class ProductSalesReport implements ReportInterface
{
    private ProductSalesReportCriteria $dto;

    public function __construct(
        private readonly ProductSalesDoctrineRepository $salesRepository,
        private readonly ProductSalesSummaryDoctrineRepository $summaryRepository,
        private readonly BarChartBuilder $barChartBuilder,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function build(object $dto): array
    {
        if (!$dto instanceof ProductSalesReportCriteria) {
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

    /**
     * @return array<int, array<string, mixed>>|null
     */
    private function getSales(): ?array
    {
        if (null !== $this->dto->productId) {
            return null;
        }

        $sales = $this->salesRepository->findByCriteria($this->dto);

        return [] === $sales ? null : $sales;
    }

    /**
     * @return array<string, mixed>|null
     */
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

        return $this->barChartBuilder->create(
            $salesData,
            $this->dto->getDuration(),
            ProductSalesMetric::from($this->dto->getSort())
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
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
