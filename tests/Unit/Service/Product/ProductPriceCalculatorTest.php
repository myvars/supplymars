<?php

namespace App\Tests\Unit\Service\Product;

use PHPUnit\Framework\MockObject\MockObject;
use App\Entity\Product;
use App\Entity\VatRate;
use App\Enum\PriceModel;
use App\Service\Product\MarkupCalculator;
use App\Service\Product\ProductPriceCalculator;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class ProductPriceCalculatorTest extends TestCase
{
    private MockObject $entityManager;

    private MockObject $markupCalculator;

    private ProductPriceCalculator $productPriceCalculator;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->markupCalculator = $this->createMock(MarkupCalculator::class);
        $this->productPriceCalculator = new ProductPriceCalculator($this->entityManager, $this->markupCalculator);
    }

    public function testRecalculatePrice(): void
    {
        $vatRate = $this->createMock(VatRate::class);
        $vatRate->method('getRate')->willReturn('15');

        $product = $this->createMock(Product::class);
        $product->method('getCost')->willReturn('100');
        $product->method('getActiveMarkup')->willReturn('20');
        $product->method('getCategoryVatRate')->willReturn($vatRate);
        $product->method('getActivePriceModel')->willReturn(PriceModel::PRETTY_99);

        $this->markupCalculator->method('calculatePrettyPrice')->willReturn('138.99');
        $this->markupCalculator->method('calculateCustomMarkup')->willReturn('20.000');
        $this->markupCalculator->method('calculateSellPrice')->willReturn('120.00');

        $product->expects($this->once())->method('setMarkup')->with('20.000');
        $product->expects($this->once())->method('setSellPrice')->with('120.00');
        $product->expects($this->once())->method('setSellPriceIncVat')->with('138.99');

        $this->entityManager->expects($this->once())->method('flush');

        $this->productPriceCalculator->recalculatePrice($product);
    }
}