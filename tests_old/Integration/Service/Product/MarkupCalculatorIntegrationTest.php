<?php

namespace App\Tests\Integration\Service\Product;

use App\Shared\Domain\Service\Pricing\MarkupCalculator;
use App\Shared\Domain\ValueObject\PriceModel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class MarkupCalculatorIntegrationTest extends KernelTestCase
{
    private MarkupCalculator $markupCalculator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->markupCalculator = static::getContainer()->get(MarkupCalculator::class);
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
}
