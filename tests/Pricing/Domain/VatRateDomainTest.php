<?php

declare(strict_types=1);

namespace App\Tests\Pricing\Domain;

use App\Pricing\Domain\Model\VatRate\Event\VatRateWasChangedEvent;
use App\Pricing\Domain\Model\VatRate\VatRate;
use PHPUnit\Framework\Attributes\TestWith;
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

    /**
     * @param numeric-string $rate
     */
    #[TestWith(['   ', '5.00', 'Rate name cannot be empty'])]
    #[TestWith(['Bad', '-1.00', 'Rate cannot be negative'])]
    public function testCreateRefusesInvalidValues(string $name, string $rate, string $message): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains($message);

        VatRate::create(name: $name, rate: $rate);
    }
}
