<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Product;
use App\Entity\ProductSales;
use App\Entity\Supplier;
use PHPUnit\Framework\TestCase;

class ProductSalesTest extends TestCase
{
    public function testCreate(): void
    {
        $product = $this->createMock(Product::class);
        $supplier = $this->createMock(Supplier::class);
        $dateString = '2023-01-01';
        $salesQty = 100;
        $salesCost = '500.00';
        $salesValue = '1000.00';

        $productSales = ProductSales::create($product, $supplier, $dateString, $salesQty, $salesCost, $salesValue);

        $this->assertSame($product, $productSales->getProduct());
        $this->assertSame($supplier, $productSales->getSupplier());
        $this->assertEquals($dateString, $productSales->getDateString());
        $this->assertEquals($salesQty, $productSales->getSalesQty());
        $this->assertEquals($salesCost, $productSales->getSalesCost());
        $this->assertEquals($salesValue, $productSales->getSalesValue());
        $this->assertInstanceOf(\DateTimeImmutable::class, $productSales->getSalesDate());
        $this->assertEquals($dateString, $productSales->getSalesDate()->format('Y-m-d'));
    }
}