<?php

namespace App\Service\Sales;

use App\Entity\OrderSales;
use App\Entity\OrderSalesSummary;
use App\Enum\SalesDuration;
use App\ValueObject\OrderSalesType;
use Doctrine\ORM\EntityManagerInterface;

class OrderSalesSummaryCalculator
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function process(bool $rebuild = false): void
    {
        foreach (SalesDuration::cases() as $salesDuration) {
            $this->processOrderSalesType(OrderSalesType::create($salesDuration, $rebuild));
        }
    }

    private function processOrderSalesType(OrderSalesType $orderSalesType): void
    {
        $sales = $this->getOrderSalesSummary($orderSalesType);

        $this->removeExistingSummary($orderSalesType);

        foreach ($sales as $sale) {
            $orderSalesSummary = OrderSalesSummary::create(
                $orderSalesType,
                $sale['dateString'],
                $sale['orderCount'],
                $sale['orderValue'],
                $sale['averageOrderValue']
            );
            $this->entityManager->persist($orderSalesSummary);
        }

        $this->entityManager->flush();
    }

    private function getOrderSalesSummary(OrderSalesType $orderSalesType): ?array
    {
        return $this->entityManager->getRepository(OrderSales::class)
            ->findOrderSalesSummary($orderSalesType);
    }

    private function removeExistingSummary(OrderSalesType $orderSalesType): void
    {
        $this->entityManager->getRepository(OrderSalesSummary::class)
            ->deleteByOrderSalesType($orderSalesType);
    }
}