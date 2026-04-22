<?php

declare(strict_types=1);

namespace App\Tests\Purchasing\Domain;

use App\Purchasing\Domain\Model\Supplier\Event\SupplierStatusWasChangedEvent;
use App\Purchasing\Domain\Model\Supplier\Supplier;
use App\Purchasing\Domain\Model\Supplier\SupplierColourScheme;
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

    public function testCreateDefaultsToDefaultColourScheme(): void
    {
        $supplier = Supplier::create(
            name: 'Supplier A',
            isActive: true,
        );

        self::assertSame(SupplierColourScheme::Violet, $supplier->getColourSchemeEnum());
        self::assertSame('supplier1', $supplier->getColourScheme());
    }

    public function testCreateWithExplicitColourScheme(): void
    {
        $supplier = Supplier::create(
            name: 'Supplier B',
            isActive: true,
            colourScheme: SupplierColourScheme::Amber,
        );

        self::assertSame(SupplierColourScheme::Amber, $supplier->getColourSchemeEnum());
        self::assertSame('supplier2', $supplier->getColourScheme());
    }

    public function testUpdatePreservesColourSchemeWhenNotProvided(): void
    {
        $supplier = Supplier::create(
            name: 'Supplier A',
            isActive: true,
            colourScheme: SupplierColourScheme::Teal,
        );

        $supplier->update(
            name: 'Supplier A Updated',
            isActive: true,
        );

        self::assertSame(SupplierColourScheme::Teal, $supplier->getColourSchemeEnum());
    }

    public function testUpdateChangesColourSchemeWhenProvided(): void
    {
        $supplier = Supplier::create(
            name: 'Supplier A',
            isActive: true,
            colourScheme: SupplierColourScheme::Violet,
        );

        $supplier->update(
            name: 'Supplier A',
            isActive: true,
            colourScheme: SupplierColourScheme::Rose,
        );

        self::assertSame(SupplierColourScheme::Rose, $supplier->getColourSchemeEnum());
        self::assertSame('supplier4', $supplier->getColourScheme());
    }

    public function testColourSchemeEnumCssPrefixes(): void
    {
        self::assertSame('supplier1', SupplierColourScheme::Violet->cssPrefix());
        self::assertSame('supplier2', SupplierColourScheme::Amber->cssPrefix());
        self::assertSame('supplier3', SupplierColourScheme::Teal->cssPrefix());
        self::assertSame('supplier4', SupplierColourScheme::Rose->cssPrefix());
    }

    public function testColourSchemeEnumChartColors(): void
    {
        self::assertSame('#8b5cf6', SupplierColourScheme::Violet->chartColor());
        self::assertSame('#f59e0b', SupplierColourScheme::Amber->chartColor());
        self::assertSame('#14b8a6', SupplierColourScheme::Teal->chartColor());
        self::assertSame('#f43f5e', SupplierColourScheme::Rose->chartColor());
    }
}
