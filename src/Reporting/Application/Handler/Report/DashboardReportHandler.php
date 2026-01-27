<?php

namespace App\Reporting\Application\Handler\Report;

use App\Order\Domain\Model\Order\CustomerOrder;
use App\Order\Infrastructure\Persistence\Doctrine\CustomerOrderDoctrineRepository;
use App\Purchasing\Infrastructure\Persistence\Doctrine\PurchaseOrderDoctrineRepository;
use App\Reporting\Domain\Metric\SalesDuration;
use App\Reporting\Domain\Metric\SalesType;
use App\Reporting\Domain\Model\SalesType\ProductSalesType;
use App\Reporting\Infrastructure\Persistence\Doctrine\OrderSalesSummaryDoctrineRepository;
use App\Reporting\Infrastructure\Persistence\Doctrine\ProductSalesDoctrineRepository;
use App\Reporting\Infrastructure\Persistence\Doctrine\ProductSalesSummaryDoctrineRepository;
use App\Shared\Application\Result;

final readonly class DashboardReportHandler
{
    public function __construct(
        private OrderSalesSummaryDoctrineRepository $orderSummaryRepository,
        private ProductSalesSummaryDoctrineRepository $productSummaryRepository,
        private CustomerOrderDoctrineRepository $orderRepository,
        private PurchaseOrderDoctrineRepository $purchaseOrderRepository,
        private ProductSalesDoctrineRepository $productSalesRepository,
    ) {
    }

    public function __invoke(): Result
    {
        return Result::ok('Report created', [
            'orderSalesSummary' => $this->getOrderSalesSummary(SalesDuration::TODAY) ?? [],
            'orderSalesCompareSummary' => $this->getOrderSalesSummary(SalesDuration::WEEK_AGO) ?? [],
            'productSalesSummary' => $this->getProductSalesSummary(SalesDuration::TODAY) ?? [],
            'productSalesCompareSummary' => $this->getProductSalesSummary(SalesDuration::WEEK_AGO) ?? [],
            'overdueOrderSummary' => $this->getOverdueOrderSummary() ?? [],
            'rejectedPoSummary' => $this->getRejectedPoSummary(),
            'latestProductSales' => $this->getLatestProductSales(),
            'latestOrders' => $this->getLatestOrders(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function getOrderSalesSummary(SalesDuration $duration): ?array
    {
        return $this->orderSummaryRepository->findOrderSalesSummary($duration);
    }

    /**
     * @return array<string, mixed>
     */
    private function getProductSalesSummary(SalesDuration $duration): ?array
    {
        return $this->productSummaryRepository->findProductSalesSummary(
            1,
            SalesType::ALL,
            $duration
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function getOverdueOrderSummary(): ?array
    {
        return $this->orderRepository->findOverdueOrdersSummary(
            new \DateTime(SalesDuration::LAST_30->getStartDate())
        );
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
