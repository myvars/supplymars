<?php

namespace App\Tests\Shared\UI\Twig\Runtime;

use App\Shared\UI\Twig\Runtime\PriceFormatExtensionRuntime;
use PHPUnit\Framework\TestCase;

final class PriceFormatExtensionRuntimeTest extends TestCase
{
    private PriceFormatExtensionRuntime $runtime;

    protected function setUp(): void
    {
        $this->runtime = new PriceFormatExtensionRuntime();
    }

    public function testZeroValue(): void
    {
        self::assertSame('£0', $this->runtime->priceRounded(0));
    }

    public function testNullDefaultsToZero(): void
    {
        self::assertSame('£0', $this->runtime->priceRounded(null));
    }

    public function testFloorsDecimalValues(): void
    {
        self::assertSame('£1,234', $this->runtime->priceRounded(1234.99));
    }

    public function testWholeNumber(): void
    {
        self::assertSame('£500', $this->runtime->priceRounded(500));
    }

    public function testCustomDecimals(): void
    {
        self::assertSame('£1,234.00', $this->runtime->priceRounded(1234.99, 2));
    }

    public function testCustomSymbol(): void
    {
        self::assertSame('$1,234', $this->runtime->priceRounded(1234.99, 0, '$'));
    }

    public function testLargeNumber(): void
    {
        self::assertSame('£1,000,000', $this->runtime->priceRounded(1000000.50));
    }

    public function testNegativeValue(): void
    {
        self::assertSame('£-100', $this->runtime->priceRounded(-99.5));
    }
}
