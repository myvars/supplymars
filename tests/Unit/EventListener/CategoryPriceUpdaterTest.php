<?php

namespace App\Tests\Unit\EventListener;


use App\Entity\Category;
use App\Entity\PriceModel;
use App\Entity\Product;
use App\Entity\Subcategory;
use App\Entity\VatRate;
use App\EventListener\CategoryPriceUpdater;
use App\Service\ProductPriceCalculator;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use PHPUnit\Framework\TestCase;

class CategoryPriceUpdaterTest extends TestCase
{
    public function testPreUpdateIdentifiesProductsWhenVatRateChanges(): void
    {
        $priceCalculatorMock = $this->createMock(ProductPriceCalculator::class);
        $eventArgsMock = $this->createMock(PreUpdateEventArgs::class);

        $vatRate = $this->createMock(VatRate::class);
        $vatRate->method('getRate')->willReturn('20.000');

        $product = $this->createMock(Product::class);
        $product->method('getId')->willReturn(1);
        $product->method('IsisActive')->willReturn(true);

        $category = new Category();
        $category->addProduct($product);

        $eventArgsMock->method('hasChangedField')
            ->willReturnCallback(function($fieldName) {
                return $fieldName == 'vatRate';
            });

        $listener = new CategoryPriceUpdater($priceCalculatorMock);
        $listener->preUpdate($category, $eventArgsMock);

        $this->assertArrayHasKey(1, $listener->getChangedProducts());
    }

    public function testPreUpdateSkipsProductsWhenVatRateDoesNotChange(): void
    {
        $priceCalculatorMock = $this->createMock(ProductPriceCalculator::class);
        $eventArgsMock = $this->createMock(PreUpdateEventArgs::class);

        $eventArgsMock->method('hasChangedField')
            ->willReturnCallback(function($fieldName) {
                return $fieldName == 'none';
            });

        $listener = new CategoryPriceUpdater($priceCalculatorMock);
        $listener->preUpdate(new Category(), $eventArgsMock);

        $this->assertEmpty($listener->getChangedProducts());
    }

    public function testPreUpdateSkipsProductsWhenVatRateChangesAndNoProducts(): void
    {
        $priceCalculatorMock = $this->createMock(ProductPriceCalculator::class);
        $eventArgsMock = $this->createMock(PreUpdateEventArgs::class);

        $vatRate = $this->createMock(VatRate::class);
        $vatRate->method('getRate')->willReturn('20.000');

        $eventArgsMock->method('hasChangedField')
            ->willReturnCallback(function($fieldName) {
                return $fieldName == 'vatRate';
            });

        $listener = new CategoryPriceUpdater($priceCalculatorMock);
        $listener->preUpdate(new Category(), $eventArgsMock);

        $this->assertEmpty($listener->getChangedProducts());
    }

    public function testPreUpdateIdentifiesProductsWhenDefaultMarkupChanges(): void
    {
        $priceCalculatorMock = $this->createMock(ProductPriceCalculator::class);
        $eventArgsMock = $this->createMock(PreUpdateEventArgs::class);

        $subcategory = $this->createMock(Subcategory::class);
        $subcategory->method('getDefaultMarkup')->willReturn('0.000');

        $product = $this->createMock(Product::class);
        $product->method('getId')->willReturn(1);
        $product->method('IsisActive')->willReturn(true);
        $product->method('getDefaultMarkup')->willReturn('0.000');
        $product->method('getSubcategory')->willReturn($subcategory);

        $category = new Category();
        $category->addProduct($product);

        $eventArgsMock->method('hasChangedField')
            ->willReturnCallback(function($fieldName) {
                return $fieldName == 'defaultMarkup';
            });

        $listener = new CategoryPriceUpdater($priceCalculatorMock);
        $listener->preUpdate($category, $eventArgsMock);

        $this->assertArrayHasKey(1, $listener->getChangedProducts());
    }

    public function testPreUpdateSkipsProductsWhenDefaultMarkupChangesAndProductMarkupSet(): void
    {
        $priceCalculatorMock = $this->createMock(ProductPriceCalculator::class);
        $eventArgsMock = $this->createMock(PreUpdateEventArgs::class);

        $subcategory = $this->createMock(Subcategory::class);
        $subcategory->method('getDefaultMarkup')->willReturn('0.000');

        $product = $this->createMock(Product::class);
        $product->method('getId')->willReturn(1);
        $product->method('IsisActive')->willReturn(true);
        $product->method('getDefaultMarkup')->willReturn('5.000');
        $product->method('getSubcategory')->willReturn($subcategory);

        $category = new Category();
        $category->addProduct($product);

        $eventArgsMock->method('hasChangedField')
            ->willReturnCallback(function($fieldName) {
                return $fieldName == 'defaultMarkup';
            });

        $listener = new CategoryPriceUpdater($priceCalculatorMock);
        $listener->preUpdate($category, $eventArgsMock);

        $this->assertEmpty($listener->getChangedProducts());
    }

    public function testPreUpdateSkipsProductsWhenDefaultMarkupChangesAndSubcategoryMarkupSet(): void
    {
        $priceCalculatorMock = $this->createMock(ProductPriceCalculator::class);
        $eventArgsMock = $this->createMock(PreUpdateEventArgs::class);

        $subcategory = $this->createMock(Subcategory::class);
        $subcategory->method('getDefaultMarkup')->willReturn('5.000');

        $product = $this->createMock(Product::class);
        $product->method('getId')->willReturn(1);
        $product->method('IsisActive')->willReturn(true);
        $product->method('getDefaultMarkup')->willReturn('0.000');
        $product->method('getSubcategory')->willReturn($subcategory);

        $category = new Category();
        $category->addProduct($product);

        $eventArgsMock->method('hasChangedField')
            ->willReturnCallback(function($fieldName) {
                return $fieldName == 'defaultMarkup';
            });

        $listener = new CategoryPriceUpdater($priceCalculatorMock);
        $listener->preUpdate($category, $eventArgsMock);

        $this->assertEmpty($listener->getChangedProducts());
    }

    public function testPreUpdateIdentifiesProductsWhenPriceModelChanges(): void
    {
        $priceCalculatorMock = $this->createMock(ProductPriceCalculator::class);
        $eventArgsMock = $this->createMock(PreUpdateEventArgs::class);

        $subcategory = $this->createMock(Subcategory::class);
        $subcategory->method('getPriceModel')->willReturn(PriceModel::NONE);

        $product = $this->createMock(Product::class);
        $product->method('getId')->willReturn(1);
        $product->method('IsisActive')->willReturn(true);
        $product->method('getPriceModel')->willReturn(PriceModel::NONE);
        $product->method('getSubcategory')->willReturn($subcategory);

        $category = new Category();
        $category->addProduct($product);

        $eventArgsMock->method('hasChangedField')
            ->willReturnCallback(function($fieldName) {
                return $fieldName == 'priceModel';
            });

        $listener = new CategoryPriceUpdater($priceCalculatorMock);
        $listener->preUpdate($category, $eventArgsMock);

        $this->assertArrayHasKey(1, $listener->getChangedProducts());
    }

    public function testPreUpdateIdentifiesProductsWhenPriceModelChangesAndProductPriceModelSet(): void
    {
        $priceCalculatorMock = $this->createMock(ProductPriceCalculator::class);
        $eventArgsMock = $this->createMock(PreUpdateEventArgs::class);

        $subcategory = $this->createMock(Subcategory::class);
        $subcategory->method('getPriceModel')->willReturn(PriceModel::NONE);

        $product = $this->createMock(Product::class);
        $product->method('getId')->willReturn(1);
        $product->method('IsisActive')->willReturn(true);
        $product->method('getPriceModel')->willReturn(PriceModel::PRETTY_99);
        $product->method('getSubcategory')->willReturn($subcategory);

        $category = new Category();
        $category->addProduct($product);

        $eventArgsMock->method('hasChangedField')
            ->willReturnCallback(function($fieldName) {
                return $fieldName == 'priceModel';
            });

        $listener = new CategoryPriceUpdater($priceCalculatorMock);
        $listener->preUpdate($category, $eventArgsMock);

        $this->assertEmpty($listener->getChangedProducts());
    }

    public function testPreUpdateIdentifiesProductsWhenPriceModelChangesAndSubcategoryPriceModelSet(): void
    {
        $priceCalculatorMock = $this->createMock(ProductPriceCalculator::class);
        $eventArgsMock = $this->createMock(PreUpdateEventArgs::class);

        $subcategory = $this->createMock(Subcategory::class);
        $subcategory->method('getPriceModel')->willReturn(PriceModel::PRETTY_99);

        $product = $this->createMock(Product::class);
        $product->method('getId')->willReturn(1);
        $product->method('IsisActive')->willReturn(true);
        $product->method('getPriceModel')->willReturn(PriceModel::NONE);
        $product->method('getSubcategory')->willReturn($subcategory);

        $category = new Category();
        $category->addProduct($product);

        $eventArgsMock->method('hasChangedField')
            ->willReturnCallback(function($fieldName) {
                return $fieldName == 'priceModel';
            });

        $listener = new CategoryPriceUpdater($priceCalculatorMock);
        $listener->preUpdate($category, $eventArgsMock);

        $this->assertEmpty($listener->getChangedProducts());
    }

    public function testPostUpdateRecalculatesPriceForChangedProducts(): void
    {
        $priceCalculatorMock = $this->createMock(ProductPriceCalculator::class);

        $product1 = $this->createMock(Product::class);
        $product1->method('getId')->willReturn(1);
        $product2 = $this->createMock(Product::class);
        $product2->method('getId')->willReturn(2);

        $listener = new CategoryPriceUpdater($priceCalculatorMock);
        $listener->setChangedProduct($product1);
        $listener->setChangedProduct($product2);

        $this->assertCount(2, $listener->getChangedProducts());

        $priceCalculatorMock->expects($this->once())
            ->method('recalculatePriceFromArray')
            ->with($listener->getChangedProducts());

        $listener->postUpdate(new Category());
    }
}