<?php

namespace App\Service\Sales;

use App\Entity\ProductSales;
use App\Entity\ProductSalesSummary;
use App\Enum\SalesDuration;
use App\Enum\SalesType;
use App\ValueObject\ProductSalesType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductSalesSummaryCalculator
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator,
    ) {
    }

    public function process(bool $rebuild = false): void
    {
        foreach (SalesDuration::cases() as $salesDuration) {
            foreach (SalesType::cases() as $salesType) {
                // Skip day duration for product sales since it is already processed
                if (SalesDuration::DAY === $salesDuration && SalesType::PRODUCT === $salesType) {
                    continue;
                }

                // Skip week ago duration for product sales
                if (SalesDuration::WEEK_AGO === $salesDuration && SalesType::PRODUCT === $salesType) {
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

            $errors = $this->validator->validate($productSalesSummary);
            if (count($errors) > 0) {
                throw new \InvalidArgumentException((string) $errors);
            }

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
