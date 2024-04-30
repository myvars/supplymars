<?php

namespace App\Tests\Unit\EventListener;


use App\Entity\PriceModel;
use App\Entity\Product;
use App\Entity\Subcategory;
use App\EventListener\SubcategoryPriceUpdater;
use App\Service\Product\ProductPriceCalculator;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use PHPUnit\Framework\TestCase;

class SubcategoryPriceUpdaterTest extends TestCase
{
    public function testPreUpdateIdentifiesProductsWhenDefaultMarkupChanges(): void
    {
        $priceCalculatorMock = $this->createMock(ProductPriceCalculator::class);
        $eventArgsMock = $this->createMock(PreUpdateEventArgs::class);

        $product = $this->createMock(Product::class);
        $product->method('getId')->willReturn(1);
        $product->method('IsisActive')->willReturn(true);
        $product->method('getDefaultMarkup')->willReturn('0.000');

        $subcategory = new Subcategory();
        $subcategory->addProduct($product);

        $eventArgsMock->method('hasChangedField')
            ->willReturnCallback(function($fieldName) {
                return $fieldName == 'defaultMarkup';
            });

        $listener = new SubcategoryPriceUpdater($priceCalculatorMock);
        $listener->preUpdate($subcategory, $eventArgsMock);

        $this->assertArrayHasKey(1, $listener->getChangedProducts());
    }

    public function testPreUpdateSkipsProductsWhenDefaultMarkupDoesNotChange(): void
    {
        $priceCalculatorMock = $this->createMock(ProductPriceCalculator::class);
        $eventArgsMock = $this->createMock(PreUpdateEventArgs::class);

        $eventArgsMock->method('hasChangedField')
            ->willReturnCallback(function($fieldName) {
                return $fieldName == 'none';
            });

        $listener = new SubcategoryPriceUpdater($priceCalculatorMock);
        $listener->preUpdate(new Subcategory(), $eventArgsMock);

        $this->assertEmpty($listener->getChangedProducts());
    }

    public function testPreUpdateSkipsProductsWhenDefaultMarkupChangesAndNoProducts(): void
    {
        $priceCalculatorMock = $this->createMock(ProductPriceCalculator::class);
        $eventArgsMock = $this->createMock(PreUpdateEventArgs::class);

        $eventArgsMock->method('hasChangedField')
            ->willReturnCallback(function($fieldName) {
                return $fieldName == 'defaultMarkup';
            });

        $listener = new SubcategoryPriceUpdater($priceCalculatorMock);
        $listener->preUpdate(new Subcategory(), $eventArgsMock);

        $this->assertEmpty($listener->getChangedProducts());
    }

    public function testPreUpdateSkipsProductsWhenDefaultMarkupChangesAndProductMarkupSet(): void
    {
        $priceCalculatorMock = $this->createMock(ProductPriceCalculator::class);
        $eventArgsMock = $this->createMock(PreUpdateEventArgs::class);

        $product = $this->createMock(Product::class);
        $product->method('getId')->willReturn(1);
        $product->method('IsisActive')->willReturn(true);
        $product->method('getDefaultMarkup')->willReturn('5.000');

        $subcategory = new Subcategory();
        $subcategory->addProduct($product);

        $eventArgsMock->method('hasChangedField')
            ->willReturnCallback(function($fieldName) {
                return $fieldName == 'defaultMarkup';
            });

        $listener = new SubcategoryPriceUpdater($priceCalculatorMock);
        $listener->preUpdate($subcategory, $eventArgsMock);

        $this->assertEmpty($listener->getChangedProducts());
    }

    public function testPreUpdateIdentifiesProductsWhenPriceModelChanges(): void
    {
        $priceCalculatorMock = $this->createMock(ProductPriceCalculator::class);
        $eventArgsMock = $this->createMock(PreUpdateEventArgs::class);

        $product = $this->createMock(Product::class);
        $product->method('getId')->willReturn(1);
        $product->method('IsisActive')->willReturn(true);
        $product->method('getPriceModel')->willReturn(PriceModel::NONE);

        $subcategory = new Subcategory();
        $subcategory->addProduct($product);

        $eventArgsMock->method('hasChangedField')
            ->willReturnCallback(function($fieldName) {
                return $fieldName == 'priceModel';
            });

        $listener = new SubcategoryPriceUpdater($priceCalculatorMock);
        $listener->preUpdate($subcategory, $eventArgsMock);

        $this->assertArrayHasKey(1, $listener->getChangedProducts());
    }

    public function testPreUpdateSkipsProductsWhenPriceModelChangesAndProductPriceModelSet(): void
    {
        $priceCalculatorMock = $this->createMock(ProductPriceCalculator::class);
        $eventArgsMock = $this->createMock(PreUpdateEventArgs::class);

        $product = $this->createMock(Product::class);
        $product->method('getId')->willReturn(1);
        $product->method('IsisActive')->willReturn(true);
        $product->method('getPriceModel')->willReturn(PriceModel::DEFAULT);

        $subcategory = new Subcategory();
        $subcategory->addProduct($product);

        $eventArgsMock->method('hasChangedField')
            ->willReturnCallback(function($fieldName) {
                return $fieldName == 'priceModel';
            });

        $listener = new SubcategoryPriceUpdater($priceCalculatorMock);
        $listener->preUpdate($subcategory, $eventArgsMock);

        $this->assertEmpty($listener->getChangedProducts());
    }

    public function testPostUpdateRecalculatesPriceForChangedProducts(): void
    {
        $priceCalculatorMock = $this->createMock(ProductPriceCalculator::class);

        $product1 = $this->createMock(Product::class);
        $product1->method('getId')->willReturn(1);
        $product2 = $this->createMock(Product::class);
        $product2->method('getId')->willReturn(2);

        $listener = new SubcategoryPriceUpdater($priceCalculatorMock);
        $listener->setChangedProduct($product1);
        $listener->setChangedProduct($product2);

        $this->assertCount(2, $listener->getChangedProducts());

        $priceCalculatorMock->expects($this->once())
            ->method('recalculatePriceFromArray')
            ->with($listener->getChangedProducts());

        $listener->postUpdate(new Subcategory());
    }
}