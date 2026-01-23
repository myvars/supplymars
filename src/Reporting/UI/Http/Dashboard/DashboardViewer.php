<?php

namespace App\Reporting\UI\Http\Dashboard;

use App\Order\Domain\Model\Order\CustomerOrder;
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

    /**
     * @return array<string, mixed>
     */
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

    /**
     * @return array<string, mixed>
     */
    private function getOrderSalesSummary(SalesDuration $duration): array
    {
        $summary = $this->orderSummaryRepository->findOrderSalesSummary($duration);

        return $summary ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    private function getProductSalesSummary(SalesDuration $duration): array
    {
        $summary = $this->productSummaryRepository->findProductSalesSummary(
            1,
            SalesType::ALL,
            $duration
        );

        return $summary ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    private function getOverdueOrderSummary(): array
    {
        $summary = $this->orderRepository->findOverdueOrdersSummary(
            new \DateTime(SalesDuration::LAST_30->getStartDate())
        );

        return $summary ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    private function getRejectedPoSummary(): array
    {
        return $this->purchaseOrderRepository->findRejectedPoSummary(
            new \DateTime(SalesDuration::LAST_30->getStartDate())
        );
    }

    /**
     * @return array<int, mixed>
     */
    private function getLatestProductSales(): array
    {
        return $this->productSalesRepository->findLatestProductSales(
            ProductSalesType::create(SalesType::ALL, SalesDuration::TODAY),
            5
        );
    }

    /**
     * @return array<int, CustomerOrder>
     */
    private function getLatestOrders(): array
    {
        return $this->orderRepository->findLatestOrders(
            new \DateTime(SalesDuration::TODAY->getStartDate()),
            5
        );
    }
}
