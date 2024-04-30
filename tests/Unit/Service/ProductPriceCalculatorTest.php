<?php

namespace App\Tests\Unit\Service;


use App\Entity\Category;
use App\Entity\PriceModel;
use App\Entity\Product;
use App\Entity\Subcategory;
use App\Entity\VatRate;
use App\Service\Product\MarkupCalculator;
use App\Service\Product\ProductPriceCalculator;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class ProductPriceCalculatorTest extends TestCase
{
    private EntityManagerInterface $entityManagerMock;
    private MarkupCalculator $markupCalculatorMock;
    private ProductPriceCalculator $productPriceCalculator;

    protected function setUp(): void
    {
        $this->entityManagerMock = $this->createMock(EntityManagerInterface::class);
        $this->markupCalculatorMock = $this->createMock(MarkupCalculator::class);
        $this->productPriceCalculator = new ProductPriceCalculator(
            $this->entityManagerMock,
            $this->markupCalculatorMock
        );
    }

    public function testRecalculatePrice(): void
    {
        $vatRate = $this->createMock(VatRate::class);
        $vatRate->method('getRate')->willReturn('20');

        $category = $this->createMock(Category::class);
        $category->method('getVatRate')->willReturn($vatRate);
        $category->method('getDefaultMarkup')->willReturn('5.000');

        $subcategory = $this->createMock(Subcategory::class);
        $subcategory->method('getDefaultMarkup')->willReturn('10.000');

        $product = new Product();
        $product->setCategory($category);
        $product->setSubcategory($subcategory);
        $product->setCost('100');
        $product->setDefaultMarkup('15.000');
        $product->setPriceModel(PriceModel::PRETTY_99);

        $this->markupCalculatorMock->method('calculatePrettyPrice')->willReturn('138.99');
        $this->markupCalculatorMock->method('calculateCustomMarkup')->willReturn('20.860');
        $this->markupCalculatorMock->method('calculateSellPrice')->willReturn('138');

        $this->entityManagerMock->expects($this->once())->method('flush');

        $this->productPriceCalculator->recalculatePrice($product);

        $this->assertEquals('20.860', $product->getMarkup());
        $this->assertEquals('138', $product->getSellPrice());
        $this->assertEquals('138.99', $product->getSellPriceIncVat());
    }
}