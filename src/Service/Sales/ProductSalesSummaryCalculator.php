<?php

namespace App\Service\Sales;

use App\DTO\ProductSalesTypeDto;
use App\Entity\ProductSales;
use App\Entity\ProductSalesSummary;
use App\Enum\Duration;
use App\Enum\SalesType;
use Doctrine\ORM\EntityManagerInterface;

class ProductSalesSummaryCalculator
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function process(bool $rebuild = false): void
    {
        foreach (Duration::cases() as $duration) {

            foreach (SalesType::cases() as $salesType) {
                // Skip day duration for product sales since it is already processed
                if ($duration === Duration::DAY && $salesType === SalesType::PRODUCT) {
                    continue;
                }

                $this->processProductSalesType(ProductSalesTypeDto::create($salesType, $duration, $rebuild));
            }
        }
    }

    public function processProductSalesType(ProductSalesTypeDto $dto): void {

        $this->removeExistingSummary($dto);

        $sales = $this->getSales($dto);

        foreach ($sales as $sale) {
            $productSalesSummary = ProductSalesSummary::create(
                $dto->getSalesType(),
                $dto->getDuration(),
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

    public function getSales(ProductSalesTypeDto $dto): ?array
    {
        return $this->entityManager->getRepository(ProductSales::class)->calculateSalesBySalesType(
            $dto->getSalesType(),
            $dto->getStartDate(),
            $dto->getEndDate(),
            $dto->getDateString()
        );
    }

    public function removeExistingSummary(ProductSalesTypeDto $dto): void
    {
        $this->entityManager->getRepository(ProductSalesSummary::class)->deleteBySalesTypeAndDuration(
            $dto->getSalesType(),
            $dto->getDuration(),
            $dto->getRangeStartDate()
        );
    }
}