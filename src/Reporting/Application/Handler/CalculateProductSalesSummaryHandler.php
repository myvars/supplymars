<?php

namespace App\Reporting\Application\Handler;

use App\Reporting\Domain\Metric\SalesDuration;
use App\Reporting\Domain\Metric\SalesType;
use App\Reporting\Domain\Model\SalesType\ProductSalesSummary;
use App\Reporting\Domain\Model\SalesType\ProductSalesType;
use App\Reporting\Domain\Repository\ProductSalesRepository;
use App\Reporting\Domain\Repository\ProductSalesSummaryRepository;
use App\Shared\Application\FlusherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class CalculateProductSalesSummaryHandler
{
    public function __construct(
        private ProductSalesRepository $productSalesRepository,
        private ProductSalesSummaryRepository $productSalesSummaryRepository,
        private FlusherInterface $flusher,
        private ValidatorInterface $validator,
    ) {
    }

    /**
     * @return array<string, int>
     */
    public function process(bool $rebuild = false, bool $dryRun = false): array
    {
        $results = [];
        foreach (SalesDuration::cases() as $salesDuration) {
            foreach (SalesType::cases() as $salesType) {
                // Skip day duration for product sales since it is already processed
                if ($salesDuration === SalesDuration::DAY && $salesType === SalesType::PRODUCT) {
                    continue;
                }

                // Skip week ago duration for product sales
                if ($salesDuration === SalesDuration::WEEK_AGO && $salesType === SalesType::PRODUCT) {
                    continue;
                }

                $key = $salesDuration->value . '-' . $salesType->value;
                $results[$key] = $this->processProductSalesType(
                    ProductSalesType::create($salesType, $salesDuration, $rebuild),
                    $dryRun
                );
            }
        }

        return $results;
    }

    private function processProductSalesType(ProductSalesType $productSalesType, bool $dryRun = false): int
    {
        $sales = $this->productSalesRepository->findProductSalesSummary($productSalesType);

        if (!$dryRun) {
            $this->productSalesSummaryRepository->deleteByProductSalesType($productSalesType);
        }

        $processed = 0;
        foreach ($sales as $sale) {
            $productSalesSummary = ProductSalesSummary::create(
                $productSalesType,
                $sale['salesId'],
                $sale['dateString'],
                $sale['salesQty'],
                $sale['salesCost'],
                $sale['salesValue'],
            );

            $errors = $this->validator->validate($productSalesSummary);
            if (count($errors) > 0) {
                throw new \InvalidArgumentException((string) $errors);
            }

            if (!$dryRun) {
                $this->productSalesSummaryRepository->add($productSalesSummary);
            }

            ++$processed;
        }

        if (!$dryRun) {
            $this->flusher->flush();
        }

        return $processed;
    }
}
