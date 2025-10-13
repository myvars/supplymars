<?php

namespace App\Tests\Reporting\Unit;

use App\Reporting\Domain\Metric\SalesDuration;
use App\Reporting\Domain\Model\SalesType\OrderSalesSummary;
use App\Reporting\Domain\Model\SalesType\OrderSalesType;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class OrderSalesSummaryTest extends TestCase
{
    public function testCreate(): void
    {
        $orderSalesType = $this->createMock(OrderSalesType::class);
        $orderSalesType->method('getDuration')->willReturn(SalesDuration::LAST_7);

        $dateString = '2023-01-01';
        $orderCount = 10;
        $orderValue = '100.00';
        $averageOrderValue = '10.00';

        $orderSalesSummary = OrderSalesSummary::create($orderSalesType, $dateString, $orderCount, $orderValue, $averageOrderValue);

        $this->assertEquals(SalesDuration::LAST_7, $orderSalesSummary->getDuration());
        $this->assertEquals($dateString, $orderSalesSummary->getDateString());
        $this->assertEquals($orderCount, $orderSalesSummary->getOrderCount());
        $this->assertEquals($orderValue, $orderSalesSummary->getOrderValue());
        $this->assertEquals($averageOrderValue, $orderSalesSummary->getAverageOrderValue());
        $this->assertInstanceOf(DateTimeImmutable::class, $orderSalesSummary->getSalesDate());
        $this->assertEquals($dateString, $orderSalesSummary->getSalesDate()->format('Y-m-d'));
    }
}
