<?php

namespace App\Tests\Unit\Entity;

use App\Entity\OrderSales;
use PHPUnit\Framework\TestCase;

class OrderSalesTest extends TestCase
{
    public function testCreate(): void
    {
        $dateString = '2023-01-01';
        $orderCount = 10;
        $orderValue = '100.00';
        $averageOrderValue = '10.00';

        $orderSales = OrderSales::create($dateString, $orderCount, $orderValue, $averageOrderValue);

        $this->assertEquals($dateString, $orderSales->getDateString());
        $this->assertEquals($orderCount, $orderSales->getOrderCount());
        $this->assertEquals($orderValue, $orderSales->getOrderValue());
        $this->assertEquals($averageOrderValue, $orderSales->getAverageOrderValue());
        $this->assertInstanceOf(\DateTimeImmutable::class, $orderSales->getSalesDate());
        $this->assertEquals($dateString, $orderSales->getSalesDate()->format('Y-m-d'));
    }
}