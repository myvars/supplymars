<?php

namespace App\Tests\Reporting\Application\Handler\Report;

use App\Reporting\Application\Handler\Report\DashboardReportHandler;
use App\Reporting\Domain\Metric\SalesDuration;
use App\Reporting\Domain\Metric\SalesType;
use App\Reporting\Domain\Model\SalesType\OrderSalesType;
use App\Reporting\Domain\Model\SalesType\ProductSalesType;
use App\Tests\Shared\Factory\CustomerOrderFactory;
use App\Tests\Shared\Factory\CustomerOrderItemFactory;
use App\Tests\Shared\Factory\OrderSalesSummaryFactory;
use App\Tests\Shared\Factory\ProductSalesFactory;
use App\Tests\Shared\Factory\ProductSalesSummaryFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class DashboardReportHandlerTest extends KernelTestCase
{
    use Factories;

    private DashboardReportHandler $handler;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(DashboardReportHandler::class);
    }

    private function createRequiredSummaryData(): void
    {
        $today = new \DateTime()->format('Y-m-d');
        $weekAgo = new \DateTime('-7 days')->format('Y-m-d');

        OrderSalesSummaryFactory::createOne([
            'orderSalesType' => OrderSalesType::create(SalesDuration::TODAY),
            'dateString' => $today,
            'orderCount' => 1,
            'orderValue' => 100.00,
        ]);

        OrderSalesSummaryFactory::createOne([
            'orderSalesType' => OrderSalesType::create(SalesDuration::WEEK_AGO),
            'dateString' => $weekAgo,
            'orderCount' => 1,
            'orderValue' => 100.00,
        ]);

        ProductSalesSummaryFactory::createOne([
            'productSalesType' => ProductSalesType::create(SalesType::ALL, SalesDuration::TODAY),
            'salesId' => 1,
            'dateString' => $today,
            'salesQty' => 1,
            'salesCost' => 50.00,
            'salesValue' => 100.00,
        ]);

        ProductSalesSummaryFactory::createOne([
            'productSalesType' => ProductSalesType::create(SalesType::ALL, SalesDuration::WEEK_AGO),
            'salesId' => 1,
            'dateString' => $weekAgo,
            'salesQty' => 1,
            'salesCost' => 50.00,
            'salesValue' => 100.00,
        ]);
    }

    public function testInvokeReturnsResultWithAllExpectedKeys(): void
    {
        $this->createRequiredSummaryData();

        $result = ($this->handler)();

        self::assertTrue($result->ok);
        self::assertArrayHasKey('orderSalesSummary', $result->payload);
        self::assertArrayHasKey('orderSalesCompareSummary', $result->payload);
        self::assertArrayHasKey('productSalesSummary', $result->payload);
        self::assertArrayHasKey('productSalesCompareSummary', $result->payload);
        self::assertArrayHasKey('overdueOrderSummary', $result->payload);
        self::assertArrayHasKey('rejectedPoSummary', $result->payload);
        self::assertArrayHasKey('latestProductSales', $result->payload);
        self::assertArrayHasKey('latestOrders', $result->payload);
    }

    public function testInvokeWithNoDataReturnsEmptyArrays(): void
    {
        $this->createRequiredSummaryData();

        $result = ($this->handler)();

        self::assertTrue($result->ok);
        self::assertIsArray($result->payload['orderSalesSummary']);
        self::assertIsArray($result->payload['productSalesSummary']);
        self::assertIsArray($result->payload['latestProductSales']);
        self::assertIsArray($result->payload['latestOrders']);
    }

    public function testInvokeReturnsLatestOrdersLimitedToFive(): void
    {
        $this->createRequiredSummaryData();

        for ($i = 0; $i < 7; ++$i) {
            $order = CustomerOrderFactory::createOne();
            CustomerOrderItemFactory::createOne(['customerOrder' => $order]);
        }

        $result = ($this->handler)();

        self::assertCount(5, $result->payload['latestOrders']);
    }

    public function testInvokeReturnsLatestProductSalesLimitedToFive(): void
    {
        $this->createRequiredSummaryData();

        $today = new \DateTime()->format('Y-m-d');

        for ($i = 0; $i < 7; ++$i) {
            ProductSalesFactory::createOne(['dateString' => $today]);
        }

        $result = ($this->handler)();

        self::assertCount(5, $result->payload['latestProductSales']);
    }
}
