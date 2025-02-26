<?php

namespace App\Tests\Unit\ValueObject;

use App\Enum\SalesDuration;
use App\Enum\SalesType;
use App\ValueObject\ProductSalesType;
use PHPUnit\Framework\TestCase;

class ProductSalesTypeTest extends TestCase
{
    public function testCreateValidProductSalesType(): void
    {
        $salesType = SalesType::PRODUCT;
        $duration = SalesDuration::LAST_7;

        $productSalesType = ProductSalesType::create($salesType, $duration);

        $this->assertSame($salesType, $productSalesType->getSalesType());
        $this->assertSame($duration, $productSalesType->getDuration());
        $this->assertSame($duration->getStartDate(), $productSalesType->getStartDate());
        $this->assertSame($duration->getEndDate(), $productSalesType->getEndDate());
        $this->assertSame($duration->getDateStringFormat(), $productSalesType->getDateString());
        $this->assertSame($duration->getRangeStartDate(), $productSalesType->getRangeStartDate());
    }

    public function testCreateProductSalesTypeWithRebuildRange(): void
    {
        $salesType = SalesType::PRODUCT;
        $duration = SalesDuration::LAST_7;

        $productSalesType = ProductSalesType::create($salesType, $duration, true);

        $this->assertSame($salesType, $productSalesType->getSalesType());
        $this->assertSame($duration, $productSalesType->getDuration());
        $this->assertSame($duration->getStartDate(true), $productSalesType->getStartDate());
        $this->assertSame($duration->getEndDate(), $productSalesType->getEndDate());
        $this->assertSame($duration->getDateStringFormat(true), $productSalesType->getDateString());
        $this->assertSame($duration->getRangeStartDate(true), $productSalesType->getRangeStartDate());
    }
}