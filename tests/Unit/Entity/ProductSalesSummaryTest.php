<?php

namespace App\Tests\Unit\Entity;

use App\Entity\ProductSalesSummary;
use App\Enum\SalesDuration;
use App\Enum\SalesType;
use App\ValueObject\ProductSalesType;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class ProductSalesSummaryTest extends TestCase
{
    public function testCreate(): void
    {
        $productSalesType = $this->createMock(ProductSalesType::class);
        $productSalesType->method('getSalesType')->willReturn(SalesType::PRODUCT);
        $productSalesType->method('getDuration')->willReturn(SalesDuration::LAST_7);

        $salesId = 1;
        $dateString = '2023-01-01';
        $salesQty = 100;
        $salesCost = '500.00';
        $salesValue = '1000.00';

        $productSalesSummary = ProductSalesSummary::create($productSalesType, $salesId, $dateString, $salesQty, $salesCost, $salesValue);

        $this->assertEquals($salesId, $productSalesSummary->getSalesId());
        $this->assertEquals(SalesType::PRODUCT, $productSalesSummary->getSalesType());
        $this->assertEquals(SalesDuration::LAST_7, $productSalesSummary->getDuration());
        $this->assertEquals($dateString, $productSalesSummary->getDateString());
        $this->assertEquals($salesQty, $productSalesSummary->getSalesQty());
        $this->assertEquals($salesCost, $productSalesSummary->getSalesCost());
        $this->assertEquals($salesValue, $productSalesSummary->getSalesValue());
        $this->assertInstanceOf(DateTimeImmutable::class, $productSalesSummary->getSalesDate());
        $this->assertEquals($dateString, $productSalesSummary->getSalesDate()->format('Y-m-d'));
    }
}