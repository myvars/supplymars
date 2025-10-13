<?php

namespace App\Tests\Audit\Unit;

use App\Audit\Domain\Model\StockChange\SupplierStockChangeLog;
use App\Shared\Domain\Event\DomainEventType;
use App\Shared\Domain\ValueObject\CostChange;
use App\Shared\Domain\ValueObject\StockChange;
use PHPUnit\Framework\TestCase;

class SupplierStockChangeLogTest extends TestCase
{
    public function testCreate(): void
    {
        $eventType = DomainEventType::SUPPLIER_PRODUCT_STOCK_CHANGED;
        $eventTimestamp = new \DateTimeImmutable();

        $supplierStockChangeLog = SupplierStockChangeLog::create(
            $eventType,
            1,
            StockChange::from(0, 100),
            CostChange::from('0.00', '50.00'),
            $eventTimestamp
        );

        $this->assertEquals($eventType, $supplierStockChangeLog->getEventType());
        $this->assertEquals(1, $supplierStockChangeLog->getSupplierProductId());
        $this->assertEquals(100, $supplierStockChangeLog->getStock());
        $this->assertEquals('50.00', $supplierStockChangeLog->getCost());
        $this->assertEquals($eventTimestamp, $supplierStockChangeLog->getEventTimestamp());
    }
}
