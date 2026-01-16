<?php

namespace App\Tests\Audit\Domain;

use App\Audit\Domain\Model\StockChange\SupplierStockChangeLog;
use App\Shared\Domain\Event\DomainEventType;
use App\Shared\Domain\ValueObject\CostChange;
use App\Shared\Domain\ValueObject\StockChange;
use PHPUnit\Framework\TestCase;

final class SupplierStockChangeDomainTest extends TestCase
{
    public function testCreateCopiesAfterValues(): void
    {
        $log = SupplierStockChangeLog::create(
            type: DomainEventType::SUPPLIER_PRODUCT_STOCK_CHANGED,
            supplierProductId: 42,
            stockChange: StockChange::from(5, 7),
            costChange: CostChange::from('2.00', '3.50'),
            occurredAt: new \DateTimeImmutable('2024-01-01T00:00:00Z'),
        );

        self::assertSame(42, $log->getSupplierProductId());
        self::assertSame(7, $log->getStock());
        self::assertSame('3.50', $log->getCost());
        self::assertSame(DomainEventType::SUPPLIER_PRODUCT_STOCK_CHANGED, $log->getEventType());
    }
}
