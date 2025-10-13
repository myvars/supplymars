<?php

namespace App\Tests\Pricing\Domain;

use App\Pricing\Domain\Model\VatRate\Event\VatRateWasChangedEvent;
use App\Pricing\Domain\Model\VatRate\VatRate;
use PHPUnit\Framework\TestCase;

final class VatRateDomainTest extends TestCase
{
    public function testCreateSetsNameAndRateAndNoEventOnSameRate(): void
    {
        $vat = VatRate::create(
            name: 'Standard',
            rate: '20.00'
        );

        self::assertSame('Standard', $vat->getName());
        self::assertSame('20.00', $vat->getRate());

        $events = $vat->releaseDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(VatRateWasChangedEvent::class, $events[0]);
    }

    public function testUpdateEmitsChangeEventWhenRateActuallyChanges(): void
    {
        $vat = VatRate::create(
            name: 'Standard',
            rate: '20.00'
        );
        $vat->releaseDomainEvents(); // clear initial

        $vat->update(
            name: 'Reduced',
            rate: '10.00'
        );

        $events = $vat->releaseDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(VatRateWasChangedEvent::class, $events[0]);
        self::assertSame('Reduced', $vat->getName());
        self::assertSame('10.00', $vat->getRate());
    }

    public function testUpdateNoEventWhenRateUnchanged(): void
    {
        $vat = VatRate::create(
            name: 'Standard',
            rate: '20.00'
        );
        $vat->releaseDomainEvents();

        $vat->update(
            name: 'Standard',
            rate: '20.00'
        );

        self::assertCount(0, $vat->releaseDomainEvents());
    }

    public function testEmptyNameThrows(): void
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('Rate name cannot be empty');

        VatRate::create(
            name: '   ',
            rate: '5.00'
        );
    }

    public function testNegativeRateThrows(): void
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('Rate cannot be negative');

        VatRate::create(
            name: 'Bad',
            rate: '-1.00'
        );
    }
}
