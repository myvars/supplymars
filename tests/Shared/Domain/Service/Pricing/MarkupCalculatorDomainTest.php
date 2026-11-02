<?php

declare(strict_types=1);

namespace App\Tests\Shared\Domain\Service\Pricing;

use App\Shared\Domain\Service\Pricing\MarkupCalculator;
use App\Shared\Domain\ValueObject\PriceModel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
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
        self::assertSame('20.000', $markup);
    }

    public function testCalculateSellPrice(): void
    {
        $sell = $this->calculator->calculateSellPrice('100', '20');
        self::assertSame('120.00', $sell);
    }

    public function testCalculateSellPriceIncVat(): void
    {
        $sellInc = $this->calculator->calculateSellPriceIncVat('100', '20', '15');
        self::assertSame('138.00', $sellInc);
    }

    public function testCalculateSellPriceIncVatWithZeroVat(): void
    {
        $sellInc = $this->calculator->calculateSellPriceIncVat('100', '20', '0');
        self::assertSame('120.00', $sellInc);
    }

    public function testCalculateSellPriceBeforeVat(): void
    {
        $beforeVat = $this->calculator->calculateSellPriceBeforeVat('138', '15');
        self::assertSame('120.00', $beforeVat);
    }

    public function testCalculatePrettyPrice(): void
    {
        $pretty = $this->calculator->calculatePrettyPrice('100', '20', '15', PriceModel::PRETTY_99);
        self::assertSame('138.99', $pretty);
    }

    public function testCalculateCustomMarkup(): void
    {
        $custom = $this->calculator->calculateCustomMarkup('100', '138', '15');
        self::assertSame('20.000', $custom);
    }

    /**
     * @param numeric-string $cost
     * @param numeric-string $markup
     */
    #[TestWith(['10', '12.345', '11.23'], 'rounds down at the third decimal')]
    #[TestWith(['10', '12.355', '11.24'], 'rounds up at the third decimal')]
    public function testSellPriceRounding(string $cost, string $markup, string $expected): void
    {
        $sell = $this->calculator->calculateSellPrice($cost, $markup);
        self::assertSame($expected, $sell);
    }

    public function testBeforeVatRoundingEdge(): void
    {
        $beforeVat = $this->calculator->calculateSellPriceBeforeVat('119.99', '20');
        self::assertSame('99.99', $beforeVat);
    }

    public function testSellPriceAtCostGivesZeroMarkup(): void
    {
        self::assertSame('0.000', $this->calculator->calculateMarkupFromSellPrice('100', '100'));
        self::assertSame('0.000', $this->calculator->calculateCustomMarkup('45.67', '54.80', '20'));
    }

    /**
     * @return iterable<string, array{\Closure(MarkupCalculator): string, string}>
     */
    public static function invalidArguments(): iterable
    {
        yield 'sell price, negative cost' => [static fn (MarkupCalculator $c): string => $c->calculateSellPrice('-1', '20'), 'Cost must be greater than 0.'];
        yield 'sell price, negative markup' => [static fn (MarkupCalculator $c): string => $c->calculateSellPrice('100', '-1'), 'Markup must not be negative.'];
        yield 'markup, zero cost' => [static fn (MarkupCalculator $c): string => $c->calculateMarkupFromSellPrice('0', '120'), 'Cost must be greater than 0.'];
        yield 'markup, sell price below cost' => [static fn (MarkupCalculator $c): string => $c->calculateMarkupFromSellPrice('100', '99.99'), 'Sell price must not be less than cost.'];
        yield 'price inc VAT, negative VAT' => [static fn (MarkupCalculator $c): string => $c->calculateSellPriceIncVat('100', '20', '-1'), 'VAT rate must not be negative.'];
        yield 'price before VAT, zero price' => [static fn (MarkupCalculator $c): string => $c->calculateSellPriceBeforeVat('0', '20'), 'Sell price (inc VAT) must be greater than 0.'];
        yield 'price before VAT, negative VAT' => [static fn (MarkupCalculator $c): string => $c->calculateSellPriceBeforeVat('100', '-1'), 'VAT rate must not be negative.'];
    }

    /**
     * @param \Closure(MarkupCalculator): string $calculate
     */
    #[DataProvider('invalidArguments')]
    public function testRefusesInvalidArguments(\Closure $calculate, string $message): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains($message);

        $calculate($this->calculator);
    }
}
