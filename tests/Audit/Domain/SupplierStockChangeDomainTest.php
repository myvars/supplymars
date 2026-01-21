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

    public function testCreateWithCostChangedEventType(): void
    {
        $log = SupplierStockChangeLog::create(
            type: DomainEventType::SUPPLIER_PRODUCT_COST_CHANGED,
            supplierProductId: 99,
            stockChange: StockChange::from(10, 10),
            costChange: CostChange::from('5.00', '7.50'),
            occurredAt: new \DateTimeImmutable('2024-06-15T12:00:00Z'),
        );

        self::assertSame(DomainEventType::SUPPLIER_PRODUCT_COST_CHANGED, $log->getEventType());
        self::assertSame(99, $log->getSupplierProductId());
        self::assertSame('7.50', $log->getCost());
    }

    public function testEventTimestampIsPreserved(): void
    {
        $timestamp = new \DateTimeImmutable('2024-03-15T14:30:00Z');

        $log = SupplierStockChangeLog::create(
            type: DomainEventType::SUPPLIER_PRODUCT_STOCK_CHANGED,
            supplierProductId: 1,
            stockChange: StockChange::from(0, 5),
            costChange: CostChange::from('1.00', '1.00'),
            occurredAt: $timestamp,
        );

        self::assertSame($timestamp, $log->getEventTimestamp());
    }

    public function testIdIsNullBeforePersistence(): void
    {
        $log = SupplierStockChangeLog::create(
            type: DomainEventType::SUPPLIER_PRODUCT_STOCK_CHANGED,
            supplierProductId: 1,
            stockChange: StockChange::from(0, 1),
            costChange: CostChange::from('0.00', '1.00'),
            occurredAt: new \DateTimeImmutable(),
        );

        self::assertNull($log->getId());
    }
}
