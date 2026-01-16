<?php

namespace App\Tests\Shared\Domain\Service\Pricing;

use App\Shared\Domain\Service\Pricing\MarkupCalculator;
use App\Shared\Domain\ValueObject\PriceModel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MarkupCalculatorDomainTest extends TestCase
{
    private MarkupCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new MarkupCalculator();
    }

    public function testCalculateMarkupFromSellPrice(): void
    {
        $markup = $this->calculator->calculateMarkupFromSellPrice('100', '120');
        self::assertEquals('20.000', $markup);
    }

    public function testCalculateSellPrice(): void
    {
        $sell = $this->calculator->calculateSellPrice('100', '20');
        self::assertEquals('120.00', $sell);
    }

    public function testCalculateSellPriceIncVat(): void
    {
        $sellInc = $this->calculator->calculateSellPriceIncVat('100', '20', '15');
        self::assertEquals('138.00', $sellInc);
    }

    public function testCalculateSellPriceIncVatWithZeroVat(): void
    {
        $sellInc = $this->calculator->calculateSellPriceIncVat('100', '20', '0');
        self::assertEquals('120.00', $sellInc);
    }

    public function testCalculateSellPriceBeforeVat(): void
    {
        $beforeVat = $this->calculator->calculateSellPriceBeforeVat('138', '15');
        self::assertEquals('120.00', $beforeVat);
    }

    public function testCalculatePrettyPrice(): void
    {
        $pretty = $this->calculator->calculatePrettyPrice('100', '20', '15', PriceModel::PRETTY_99);
        self::assertEquals('138.99', $pretty);
    }

    public function testCalculateCustomMarkup(): void
    {
        $custom = $this->calculator->calculateCustomMarkup('100', '138', '15');
        self::assertEquals('20.000', $custom);
    }

    #[DataProvider('sellPriceRoundingProvider')]
    public function testSellPriceRounding(string $cost, string $markup, string $expected): void
    {
        $sell = $this->calculator->calculateSellPrice($cost, $markup);
        self::assertEquals($expected, $sell);
    }

    public static function sellPriceRoundingProvider(): array
    {
        return [
            ['10', '12.345', '11.23'], // rounds down at 3rd dp
            ['10', '12.355', '11.24'], // rounds up at 3rd dp
        ];
    }

    public function testBeforeVatRoundingEdge(): void
    {
        $beforeVat = $this->calculator->calculateSellPriceBeforeVat('119.99', '20');
        self::assertEquals('99.99', $beforeVat);
    }

    public function testInvalidCostForSellPrice(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cost must be greater than 0.');
        $this->calculator->calculateSellPrice('-1', '20');
    }

    public function testInvalidMarkupForSellPrice(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Markup must not be negative.');
        $this->calculator->calculateSellPrice('100', '-1');
    }

    public function testInvalidCostForMarkupFromSellPrice(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cost must be greater than 0.');
        $this->calculator->calculateMarkupFromSellPrice('0', '120');
    }

    public function testInvalidSellPriceForMarkupFromSellPrice(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Sell price must be greater than cost.');
        $this->calculator->calculateMarkupFromSellPrice('100', '0');
    }

    public function testInvalidVatForSellPriceIncVat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('VAT rate must not be negative.');
        $this->calculator->calculateSellPriceIncVat('100', '20', '-1');
    }

    public function testInvalidSellPriceIncVatForBeforeVat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Sell price (inc VAT) must be greater than 0.');
        $this->calculator->calculateSellPriceBeforeVat('0', '20');
    }

    public function testInvalidVatForBeforeVat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('VAT rate must not be negative.');
        $this->calculator->calculateSellPriceBeforeVat('100', '-1');
    }
}
