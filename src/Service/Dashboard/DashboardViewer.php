<?php

namespace App\Service\Dashboard;

use App\Enum\SalesDuration;
use App\Enum\SalesType;
use App\Repository\CustomerOrderRepository;
use App\Repository\OrderSalesSummaryRepository;
use App\Repository\ProductSalesRepository;
use App\Repository\ProductSalesSummaryRepository;
use App\Repository\PurchaseOrderRepository;
use App\ValueObject\ProductSalesType;
use DateTime;

final class DashboardViewer
{
    public function __construct(
        private readonly OrderSalesSummaryRepository $orderSummaryRepository,
        private readonly ProductSalesSummaryRepository $productSummaryRepository,
        private readonly CustomerOrderRepository $orderRepository,
        private readonly PurchaseOrderRepository $purchaseOrderRepository,
        private readonly ProductSalesRepository $productSalesRepository,
    ) {
    }

    public function build(): ?array
    {
        return [
            'orderSalesSummary' => $this->getOrderSalesSummary(SalesDuration::TODAY),
            'orderSalesCompareSummary' => $this->getOrderSalesSummary(SalesDuration::WEEK_AGO),
            'productSalesSummary' => $this->getProductSalesSummary(SalesDuration::TODAY),
            'productSalesCompareSummary' => $this->getProductSalesSummary(SalesDuration::WEEK_AGO),
            'overdueOrderSummary' => $this->getOverdueOrderSummary(),
            'rejectedPoSummary' => $this->getRejectedPoSummary(),
            'latestProductSales' => $this->getLatestProductSales(),
            'latestOrders' => $this->getLatestOrders(),
        ];
    }

    private function getOrderSalesSummary(SalesDuration $duration): ?array
    {
        $summary = $this->orderSummaryRepository->findOrderSalesSummary($duration);

        return $summary ?? [];
    }

    private function getProductSalesSummary(SalesDuration $duration): ?array
    {
        $summary = $this->productSummaryRepository->findProductSalesSummary(
            1,
            SalesType::ALL,
            $duration
        );

        return $summary ?? [];
    }

    private function getOverdueOrderSummary(): ?array
    {
        $summary = $this->orderRepository->findOverdueOrdersSummary(
            new DateTime(SalesDuration::LAST_30->getStartDate())
        );

        return $summary ?? [];
    }

    private function getRejectedPoSummary(): ?array
    {
        $summary = $this->purchaseOrderRepository->findRejectedPoSummary(
            new DateTime(SalesDuration::LAST_30->getStartDate())
        );

        return $summary ?? [];
    }

    private function getLatestProductSales(): ?array
    {
        return $this->productSalesRepository->findLatestProductSales(
            ProductSalesType::create(SalesType::ALL, SalesDuration::TODAY),
            5
        );
    }

    private function getLatestOrders(): ?array
    {
        return $this->orderRepository->findLatestOrders(
            new DateTime(SalesDuration::TODAY->getStartDate()),
            5
        );
    }
}