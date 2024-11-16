<?php

namespace App\Service\Sales;

use App\Entity\ProductSales;
use App\Entity\ProductSalesSummary;
use Doctrine\ORM\EntityManagerInterface;
use DateTime;

class ProductSalesSummaryProcessor
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function process(
        string $salesType,
        string $duration,
        string $startDate,
        string $dateString,
        bool $durationRange
    ): void {
        $endDate = (new DateTime('+1 day'))->format('Y-m-d');
        $sales = $this->getSales($salesType, $startDate, $endDate, $dateString);

        $this->removeExistingSummary($salesType, $duration, $durationRange ? $startDate : null);

        foreach ($sales as $sale) {
                $productSalesSummary = ProductSalesSummary::create(
                    $sale['salesId'],
                    $salesType,
                    $duration,
                    $sale['dateString'],
                    $sale['salesQty'],
                    $sale['salesCost'],
                    $sale['salesValue'],
                );
                $this->entityManager->persist($productSalesSummary);
        }

        $this->entityManager->flush();
    }

    public function getSales(string $salesType, string $startDate, string $endDate, string $dateString): ?array
    {
        return $this->entityManager->getRepository(ProductSales::class)
            ->calculateSalesBySalesType($salesType, $startDate, $endDate, $dateString);
    }

    public function removeExistingSummary(string $salesType, string $duration, ?string $dateString): void
    {
        $this->entityManager->getRepository(ProductSalesSummary::class)
            ->deleteBySalesTypeAndDuration($salesType, $duration, $dateString);
    }
}