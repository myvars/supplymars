<?php

namespace App\Tests\Purchasing\Domain;

use App\Purchasing\Domain\Model\Supplier\Event\SupplierStatusWasChangedEvent;
use App\Purchasing\Domain\Model\Supplier\Supplier;
use PHPUnit\Framework\TestCase;

class SupplierDomainTest extends TestCase
{
    public function testCreateTrimsNameAndSetsActive(): void
    {
        $supplier = Supplier::create(
            name: '  Turtle Inc  ',
            isActive: true,
        );

        self::assertSame('Turtle Inc', $supplier->getName());
        self::assertTrue($supplier->isActive());
    }

    public function testCreateEmitsStatusEventForChangedStatus(): void
    {
        $supplier = Supplier::create(
            name: 'Supplier A',
            isActive: true,
        );

        $events = $supplier->releaseDomainEvents();
        self::assertCount(1, $events);
        $event = $events[0];
        self::assertInstanceOf(SupplierStatusWasChangedEvent::class, $event);
        self::assertTrue($event->isActivated());
    }

    public function testChangeStatusEmitsEventWhenValueChanges(): void
    {
        $supplier = Supplier::create(
            name: 'Supplier A',
            isActive: true,
        );
        $supplier->releaseDomainEvents(); // clear initial event

        $supplier->update(
            name: 'Supplier A',
            isActive: false,
        );

        $events = $supplier->releaseDomainEvents();
        self::assertCount(1, $events);
        $event = $events[0];
        self::assertInstanceOf(SupplierStatusWasChangedEvent::class, $event);
        self::assertFalse($event->isActivated());
    }

    public function testChangeStatusNoEventWhenNothingChanges(): void
    {
        $supplier = Supplier::create(
            name: 'Supplier A',
            isActive: true,
        );
        $supplier->releaseDomainEvents();

        $supplier->update(
            name: 'Supplier A',
            isActive: true,
        );

        self::assertCount(0, $supplier->releaseDomainEvents());
    }

    public function testInvalidNameThrows(): void
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('Supplier name cannot be empty');

        Supplier::create(
            name: '',
            isActive: true,
        );
    }
}
