<?php

namespace App\Tests\Unit\Service\Product;

use App\Enum\PriceModel;
use App\Service\Product\MarkupCalculator;
use PHPUnit\Framework\TestCase;

class MarkupCalculatorTest extends TestCase
{
    private MarkupCalculator $markupCalculator;

    protected function setUp(): void
    {
        $this->markupCalculator = new MarkupCalculator();
    }

    public function testCalculateMarkupFromSellPrice(): void
    {
        $cost = '100';
        $sellPrice = '120';
        $expectedMarkup = '20.000';

        $markup = $this->markupCalculator->calculateMarkupFromSellPrice($cost, $sellPrice);

        $this->assertEquals($expectedMarkup, $markup);
    }

    public function testCalculateSellPrice(): void
    {
        $cost = '100';
        $markup = '20';
        $expectedSellPrice = '120.00';

        $sellPrice = $this->markupCalculator->calculateSellPrice($cost, $markup);

        $this->assertEquals($expectedSellPrice, $sellPrice);
    }

    public function testCalculateSellPriceWithNegativeCost(): void
    {
        $cost = '-1';
        $markup = '20';

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cost must be greater than 0.');

        $this->markupCalculator->calculateSellPrice($cost, $markup);
    }

    public function testCalculateSellPriceWithNegativeMarkup(): void
    {
        $cost = '100';
        $markup = '-1';

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Markup must not be negative.');

        $this->markupCalculator->calculateSellPrice($cost, $markup);
    }

    public function testCalculateSellPriceIncVat(): void
    {
        $cost = '100';
        $markup = '20';
        $vatRate = '15';
        $expectedSellPriceIncVat = '138.00';

        $sellPriceIncVat = $this->markupCalculator->calculateSellPriceIncVat($cost, $markup, $vatRate);

        $this->assertEquals($expectedSellPriceIncVat, $sellPriceIncVat);
    }

    public function testCalculateSellPriceBeforeVat(): void
    {
        $sellPriceIncVat = '138';
        $vatRate = '15';
        $expectedSellPriceBeforeVat = '120.00';

        $sellPriceBeforeVat = $this->markupCalculator->calculateSellPriceBeforeVat($sellPriceIncVat, $vatRate);

        $this->assertEquals($expectedSellPriceBeforeVat, $sellPriceBeforeVat);
    }

    public function testCalculateSellPriceBeforeVatWithNegativeVat(): void
    {
        $sellPriceIncVat = '138';
        $vatRate = '-1';

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('VAT rate must not be negative.');

        $this->markupCalculator->calculateSellPriceBeforeVat($sellPriceIncVat, $vatRate);
    }

    public function testCalculatePrettyPrice(): void
    {
        $cost = '100';
        $markup = '20';
        $vatRate = '15';
        $priceModel = PriceModel::PRETTY_99;

        $expectedPrettyPrice = '138.99';

        $prettyPrice = $this->markupCalculator->calculatePrettyPrice($cost, $markup, $vatRate, $priceModel);

        $this->assertEquals($expectedPrettyPrice, $prettyPrice);
    }

    public function testCalculateCustomMarkup(): void
    {
        $cost = '100';
        $sellPriceIncVat = '138';
        $vatRate = '15';
        $expectedCustomMarkup = '20.000';

        $customMarkup = $this->markupCalculator->calculateCustomMarkup($cost, $sellPriceIncVat, $vatRate);

        $this->assertEquals($expectedCustomMarkup, $customMarkup);
    }

    public function testInvalidCostThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cost must be greater than 0.');

        $this->markupCalculator->calculateMarkupFromSellPrice('0', '120');
    }

    public function testInvalidSellPriceThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Sell price must be greater than cost.');

        $this->markupCalculator->calculateMarkupFromSellPrice('100', '0');
    }

    public function testInvalidVatRateThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('VAT rate must not be negative.');

        $this->markupCalculator->calculateSellPriceIncVat('100', '20', '-1');
    }

    public function testInvalidSellPriceIncVatThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Sell price (inc VAT) must be greater than 0.');

        $this->markupCalculator->calculateSellPriceBeforeVat('0', '20');
    }
}