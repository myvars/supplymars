<?php

namespace App\Reporting\Application\Handler;

use App\Reporting\Domain\Metric\SalesDuration;
use App\Reporting\Domain\Metric\SalesType;
use App\Reporting\Domain\Model\SalesType\ProductSales;
use App\Reporting\Domain\Model\SalesType\ProductSalesSummary;
use App\Reporting\Domain\Model\SalesType\ProductSalesType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class CalculateProductSalesSummaryHandler
{
    public function __construct(
        private EntityManagerInterface $em,
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
        $sales = $this->getSales($productSalesType);

        if (!$dryRun) {
            $this->removeExistingSummary($productSalesType);
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
                $this->em->persist($productSalesSummary);
            }

            ++$processed;
        }

        if (!$dryRun) {
            $this->em->flush();
        }

        return $processed;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getSales(ProductSalesType $productSalesType): array
    {
        return $this->em->getRepository(ProductSales::class)
            ->findProductSalesSummary($productSalesType);
    }

    private function removeExistingSummary(ProductSalesType $productSalesType): void
    {
        $this->em->getRepository(ProductSalesSummary::class)
            ->deleteByProductSalesType($productSalesType);
    }
}
