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

            $this->em->persist($productSalesSummary);
        }

        $this->em->flush();
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
