<?php

namespace App\Service\Sales;

use App\Entity\ProductSales;
use App\Entity\ProductSalesSummary;
use App\Enum\SalesDuration;
use App\Enum\SalesType;
use App\ValueObject\ProductSalesType;
use Doctrine\ORM\EntityManagerInterface;

class ProductSalesSummaryCalculator
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function process(bool $rebuild = false): void
    {
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

                $this->processProductSalesType(ProductSalesType::create($salesType, $salesDuration, $rebuild));
            }
        }
    }

    private function processProductSalesType(ProductSalesType $productSalesType): void
    {
        $sales = $this->getSales($productSalesType);

        $this->removeExistingSummary($productSalesType);

        foreach ($sales as $sale) {
            $productSalesSummary = ProductSalesSummary::create(
                $productSalesType,
                $sale['salesId'],
                $sale['dateString'],
                $sale['salesQty'],
                $sale['salesCost'],
                $sale['salesValue'],
            );
            $this->entityManager->persist($productSalesSummary);
        }

        $this->entityManager->flush();
    }

    private function getSales(ProductSalesType $productSalesType): ?array
    {
        return $this->entityManager->getRepository(ProductSales::class)
            ->findProductSalesSummary($productSalesType);
    }

    private function removeExistingSummary(ProductSalesType $productSalesType): void
    {
        $this->entityManager->getRepository(ProductSalesSummary::class)
            ->deleteByProductSalesType($productSalesType);
    }
}