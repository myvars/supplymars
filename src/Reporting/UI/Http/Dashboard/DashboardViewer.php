<?php

namespace App\Reporting\UI\Http\Dashboard;

use App\Order\Infrastructure\Persistence\Doctrine\CustomerOrderDoctrineRepository;
use App\Purchasing\Infrastructure\Persistence\Doctrine\PurchaseOrderDoctrineRepository;
use App\Reporting\Domain\Metric\SalesDuration;
use App\Reporting\Domain\Metric\SalesType;
use App\Reporting\Domain\Model\SalesType\ProductSalesType;
use App\Reporting\Infrastructure\Persistence\Doctrine\OrderSalesSummaryDoctrineRepository;
use App\Reporting\Infrastructure\Persistence\Doctrine\ProductSalesDoctrineRepository;
use App\Reporting\Infrastructure\Persistence\Doctrine\ProductSalesSummaryDoctrineRepository;

final readonly class DashboardViewer
{
    public function __construct(
        private OrderSalesSummaryDoctrineRepository $orderSummaryRepository,
        private ProductSalesSummaryDoctrineRepository $productSummaryRepository,
        private CustomerOrderDoctrineRepository $orderRepository,
        private PurchaseOrderDoctrineRepository $purchaseOrderRepository,
        private ProductSalesDoctrineRepository $productSalesRepository,
    ) {
    }

    public function build(): array
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

    private function getOrderSalesSummary(SalesDuration $duration): array
    {
        $summary = $this->orderSummaryRepository->findOrderSalesSummary($duration);

        return $summary ?? [];
    }

    private function getProductSalesSummary(SalesDuration $duration): array
    {
        $summary = $this->productSummaryRepository->findProductSalesSummary(
            1,
            SalesType::ALL,
            $duration
        );

        return $summary ?? [];
    }

    private function getOverdueOrderSummary(): array
    {
        $summary = $this->orderRepository->findOverdueOrdersSummary(
            new \DateTime(SalesDuration::LAST_30->getStartDate())
        );

        return $summary ?? [];
    }

    private function getRejectedPoSummary(): array
    {
        $summary = $this->purchaseOrderRepository->findRejectedPoSummary(
            new \DateTime(SalesDuration::LAST_30->getStartDate())
        );

        return $summary ?? [];
    }

    private function getLatestProductSales(): array
    {
        return $this->productSalesRepository->findLatestProductSales(
            ProductSalesType::create(SalesType::ALL, SalesDuration::TODAY),
            5
        );
    }

    private function getLatestOrders(): array
    {
        return $this->orderRepository->findLatestOrders(
            new \DateTime(SalesDuration::TODAY->getStartDate()),
            5
        );
    }
}
