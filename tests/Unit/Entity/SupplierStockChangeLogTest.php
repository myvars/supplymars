<?php

namespace App\Tests\Unit\Entity;

use App\Entity\SupplierProduct;
use App\Entity\SupplierStockChangeLog;
use App\Enum\DomainEventType;
use PHPUnit\Framework\TestCase;

class SupplierStockChangeLogTest extends TestCase
{
    public function testCreate(): void
    {
        $eventType = DomainEventType::SUPPLIER_PRODUCT_STOCK_CHANGED;
        $supplierProduct = $this->createMock(SupplierProduct::class);
        $supplierProduct->method('getId')->willReturn(1);
        $supplierProduct->method('getStock')->willReturn(100);
        $supplierProduct->method('getCost')->willReturn('50.00');
        $eventTimestamp = new \DateTimeImmutable();

        $supplierStockChangeLog = SupplierStockChangeLog::create(
            $eventType,
            $supplierProduct,
            $eventTimestamp
        );

        $this->assertEquals($eventType, $supplierStockChangeLog->getEventType());
        $this->assertEquals(1, $supplierStockChangeLog->getSupplierProductId());
        $this->assertEquals(100, $supplierStockChangeLog->getStock());
        $this->assertEquals('50.00', $supplierStockChangeLog->getCost());
        $this->assertEquals($eventTimestamp, $supplierStockChangeLog->getEventTimestamp());
    }
}