<?php

namespace App\Tests\Reporting\Unit;

use App\Reporting\Domain\Metric\SalesDuration;
use App\Reporting\Domain\Model\SalesType\OrderSalesType;
use PHPUnit\Framework\TestCase;

class OrderSalesTypeTest extends TestCase
{
    public function testCreateValidOrderSalesType(): void
    {
        $duration = SalesDuration::LAST_7;

        $orderSalesType = OrderSalesType::create($duration);

        $this->assertSame($duration, $orderSalesType->getDuration());
        $this->assertSame($duration->getStartDate(), $orderSalesType->getStartDate());
        $this->assertSame($duration->getEndDate(), $orderSalesType->getEndDate());
        $this->assertSame($duration->getDateStringFormat(), $orderSalesType->getDateString());
        $this->assertSame($duration->getRangeStartDate(), $orderSalesType->getRangeStartDate());
    }

    public function testCreateOrderSalesTypeWithRebuildRange(): void
    {
        $duration = SalesDuration::LAST_7;

        $orderSalesType = OrderSalesType::create($duration, true);

        $this->assertSame($duration, $orderSalesType->getDuration());
        $this->assertSame($duration->getStartDate(true), $orderSalesType->getStartDate());
        $this->assertSame($duration->getEndDate(), $orderSalesType->getEndDate());
        $this->assertSame($duration->getDateStringFormat(true), $orderSalesType->getDateString());
        $this->assertSame($duration->getRangeStartDate(true), $orderSalesType->getRangeStartDate());
    }
}
