<?php

namespace App\Tests\Unit\EventListener;


use App\Entity\Category;
use App\Entity\Product;
use App\Entity\VatRate;
use App\EventListener\VatRatePriceUpdater;
use App\Service\Product\ProductPriceCalculator;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use PHPUnit\Framework\TestCase;

class VatRatePriceUpdaterTest extends TestCase
{
    public function testPreUpdateIdentifiesProductsWhenVatRateChanges(): void
    {
        $priceCalculatorMock = $this->createMock(ProductPriceCalculator::class);
        $eventArgsMock = $this->createMock(PreUpdateEventArgs::class);

        $product = $this->createMock(Product::class);
        $product->method('getId')->willReturn(1);
        $product->method('IsisActive')->willReturn(true);
        $product->method('getDefaultMarkup')->willReturn('0.000');

        $category = new Category();
        $category->addProduct($product);

        $vatRate = new VatRate();
        $vatRate->addCategory($category);

        $eventArgsMock->method('hasChangedField')
            ->willReturnCallback(function($fieldName) {
                return $fieldName == 'rate';
            });

        $listener = new VatRatePriceUpdater($priceCalculatorMock);
        $listener->preUpdate($vatRate, $eventArgsMock);

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

        $listener = new VatRatePriceUpdater($priceCalculatorMock);
        $listener->preUpdate(new VatRate(), $eventArgsMock);

        $this->assertEmpty($listener->getChangedProducts());
    }

    public function testPreUpdateSkipsProductsWhenVatRateChangesAndNoCategories(): void
    {
        $priceCalculatorMock = $this->createMock(ProductPriceCalculator::class);
        $eventArgsMock = $this->createMock(PreUpdateEventArgs::class);

        $eventArgsMock->method('hasChangedField')
            ->willReturnCallback(function($fieldName) {
                return $fieldName == 'rate';
            });

        $listener = new VatRatePriceUpdater($priceCalculatorMock);
        $listener->preUpdate(new VatRate(), $eventArgsMock);

        $this->assertEmpty($listener->getChangedProducts());
    }

    public function testPreUpdateSkipsProductsWhenVatRateChangesAndNoCategoryProducts(): void
    {
        $priceCalculatorMock = $this->createMock(ProductPriceCalculator::class);
        $eventArgsMock = $this->createMock(PreUpdateEventArgs::class);

        $vatRate = new VatRate();
        $vatRate->addCategory(new Category());

        $eventArgsMock->method('hasChangedField')
            ->willReturnCallback(function($fieldName) {
                return $fieldName == 'rate';
            });

        $listener = new VatRatePriceUpdater($priceCalculatorMock);
        $listener->preUpdate($vatRate, $eventArgsMock);

        $this->assertEmpty($listener->getChangedProducts());
    }

    public function testPostUpdateRecalculatesPriceForChangedProducts(): void
    {
        $priceCalculatorMock = $this->createMock(ProductPriceCalculator::class);

        $product1 = $this->createMock(Product::class);
        $product1->method('getId')->willReturn(1);
        $product2 = $this->createMock(Product::class);
        $product2->method('getId')->willReturn(2);

        $listener = new VatRatePriceUpdater($priceCalculatorMock);
        $listener->setChangedProduct($product1);
        $listener->setChangedProduct($product2);

        $this->assertCount(2, $listener->getChangedProducts());

        $priceCalculatorMock->expects($this->once())
            ->method('recalculatePriceFromArray')
            ->with($listener->getChangedProducts());

        $listener->postUpdate(new VatRate());
    }
}