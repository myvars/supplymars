<?php

namespace App\Reporting\Application\Handler\Report;

use App\Reporting\Application\Report\ProductSalesReportCriteria;
use App\Reporting\Domain\Metric\ProductSalesMetric;
use App\Reporting\Domain\Metric\SalesDuration;
use App\Reporting\Domain\Metric\SalesType;
use App\Reporting\Domain\Service\SalesDateRangeResolver;
use App\Reporting\Infrastructure\Persistence\Doctrine\ProductSalesDoctrineRepository;
use App\Reporting\Infrastructure\Persistence\Doctrine\ProductSalesSummaryDoctrineRepository;
use App\Reporting\UI\Http\Chart\ProductSalesChartBuilder;
use App\Shared\Application\Result;

final readonly class ProductSalesReportHandler
{
    public function __construct(
        private ProductSalesDoctrineRepository $salesRepository,
        private ProductSalesSummaryDoctrineRepository $summaryRepository,
        private ProductSalesChartBuilder $productSalesChartBuilder,
        private SalesDateRangeResolver $dateRangeResolver,
    ) {
    }

    public function __invoke(ProductSalesReportCriteria $criteria): Result
    {
        $sales = null;
        $summary = null;
        $productSalesChart = null;
        $singleSalesType = $this->getSingleSalesType($criteria);

        if ($criteria->productId === null) {
            $sales = $this->salesRepository->findByCriteria($criteria) ?: null;
        }

        if ($singleSalesType !== null) {
            $salesTypeId = $singleSalesType['salesTypeId'];
            $salesType = $singleSalesType['salesType'];
            $duration = $criteria->getDuration();
            $salesRangeDuration = $this->dateRangeResolver->getRangeDuration($duration);

            $summary = $this->summaryRepository->findProductSalesSummary($salesTypeId, $salesType, $duration) ?? [];

            $salesData = $this->shouldUseDailySales($salesType, $salesRangeDuration) ?
                $this->salesRepository->findProductSalesRange(
                    $salesTypeId,
                    $duration->getStartDate(),
                    $duration->getEndDate()
                )
                : $this->summaryRepository->findProductSalesSummaryRange(
                    $salesTypeId,
                    $salesType,
                    $salesRangeDuration,
                    $this->dateRangeResolver->getRangeStartDate($duration)
                );

            if ($salesData !== []) {
                $productSalesChart = $this->productSalesChartBuilder->create(
                    $salesData,
                    ProductSalesMetric::from($criteria->getSort()),
                    $duration
                );
            }
        }

        return Result::ok('Report created', [
            'type' => $singleSalesType['salesType']->value ?? null,
            'sales' => $sales,
            'summary' => $summary,
            'productSalesChart' => $productSalesChart,
        ]);
    }

    /**
     * @return array{salesType: SalesType, salesTypeId: int}|null
     */
    private function getSingleSalesType(ProductSalesReportCriteria $criteria): ?array
    {
        $identifiers = array_filter([
            'product' => $criteria->productId,
            'category' => $criteria->categoryId,
            'subcategory' => $criteria->subcategoryId,
            'manufacturer' => $criteria->manufacturerId,
            'supplier' => $criteria->supplierId,
        ]);

        return match (\count($identifiers)) {
            0 => [
                'salesType' => SalesType::ALL,
                'salesTypeId' => 1,
            ],
            1 => [
                'salesType' => SalesType::from((string) array_key_first($identifiers)),
                'salesTypeId' => reset($identifiers),
            ],
            default => null,
        };
    }

    private function shouldUseDailySales(SalesType $salesType, SalesDuration $duration): bool
    {
        return $salesType === SalesType::PRODUCT && $duration === SalesDuration::DAY;
    }
}
