<?php

namespace App\Tests\Unit\Service\Dashboard;

use App\Order\Infrastructure\Persistence\Doctrine\CustomerOrderDoctrineRepository;
use App\Purchasing\Infrastructure\Persistence\Doctrine\PurchaseOrderDoctrineRepository;
use App\Reporting\Infrastructure\Persistence\Doctrine\OrderSalesSummaryDoctrineRepository;
use App\Reporting\Infrastructure\Persistence\Doctrine\ProductSalesDoctrineRepository;
use App\Reporting\Infrastructure\Persistence\Doctrine\ProductSalesSummaryDoctrineRepository;
use App\Reporting\UI\Http\Dashboard\DashboardViewer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DashboardViewerTest extends TestCase
{
    private MockObject $orderSummaryRepositoryMock;

    private MockObject $productSummaryRepositoryMock;

    private MockObject $orderRepositoryMock;

    private MockObject $purchaseOrderRepositoryMock;

    private MockObject $productSalesRepositoryMock;

    private DashboardViewer $dashboardViewer;

    protected function setUp(): void
    {
        $this->orderSummaryRepositoryMock = $this->createMock(OrderSalesSummaryDoctrineRepository::class);
        $this->productSummaryRepositoryMock = $this->createMock(ProductSalesSummaryDoctrineRepository::class);
        $this->orderRepositoryMock = $this->createMock(CustomerOrderDoctrineRepository::class);
        $this->purchaseOrderRepositoryMock = $this->createMock(PurchaseOrderDoctrineRepository::class);
        $this->productSalesRepositoryMock = $this->createMock(ProductSalesDoctrineRepository::class);

        $this->dashboardViewer = new DashboardViewer(
            $this->orderSummaryRepositoryMock,
            $this->productSummaryRepositoryMock,
            $this->orderRepositoryMock,
            $this->purchaseOrderRepositoryMock,
            $this->productSalesRepositoryMock
        );
    }

    public function testBuild(): void
    {
        $this->orderSummaryRepositoryMock->method('findOrderSalesSummary')->willReturn([]);
        $this->productSummaryRepositoryMock->method('findProductSalesSummary')->willReturn([]);
        $this->orderRepositoryMock->method('findOverdueOrdersSummary')->willReturn([]);
        $this->purchaseOrderRepositoryMock->method('findRejectedPoSummary')->willReturn([]);
        $this->productSalesRepositoryMock->method('findLatestProductSales')->willReturn([]);
        $this->orderRepositoryMock->method('findLatestOrders')->willReturn([]);

        $result = $this->dashboardViewer->build();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('orderSalesSummary', $result);
        $this->assertArrayHasKey('orderSalesCompareSummary', $result);
        $this->assertArrayHasKey('productSalesSummary', $result);
        $this->assertArrayHasKey('productSalesCompareSummary', $result);
        $this->assertArrayHasKey('overdueOrderSummary', $result);
        $this->assertArrayHasKey('rejectedPoSummary', $result);
        $this->assertArrayHasKey('latestProductSales', $result);
        $this->assertArrayHasKey('latestOrders', $result);
    }
}
