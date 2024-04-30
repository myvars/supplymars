<?php

namespace App\Tests\Unit\EventListener;


use App\Entity\Product;
use App\Entity\Supplier;
use App\Entity\SupplierProduct;
use App\EventListener\SupplierStockUpdater;
use App\Service\Product\ActiveSourceCalculator;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use PHPUnit\Framework\TestCase;

class SupplierStockUpdaterTest extends TestCase
{
    public function testPreUpdateIdentifiesSupplierProductsWhenSupplierStatusChanges(): void
    {
        $activeSourceCalculatorMock = $this->createMock(ActiveSourceCalculator::class);
        $eventArgsMock = $this->createMock(PreUpdateEventArgs::class);

        $supplierProduct = $this->createMock(SupplierProduct::class);
        $supplierProduct->method('getId')->willReturn(1);

        $supplier = new Supplier();
        $supplier->addSupplierProduct($supplierProduct);

        $eventArgsMock->method('hasChangedField')
            ->willReturnCallback(function($fieldName) {
                return $fieldName == 'isActive';
            });

        $listener = new SupplierStockUpdater($activeSourceCalculatorMock);
        $listener->preUpdate($supplier, $eventArgsMock);

        $this->assertArrayHasKey(1, $listener->getChangedSupplierProducts());
    }

    public function testPreUpdateSkipsSupplierProductsWhenSupplierStatusDoesNotChange(): void
    {
        $activeSourceCalculatorMock = $this->createMock(ActiveSourceCalculator::class);
        $eventArgsMock = $this->createMock(PreUpdateEventArgs::class);

        $eventArgsMock->method('hasChangedField')
            ->willReturnCallback(function($fieldName) {
                return $fieldName == 'none';
            });

        $listener = new SupplierStockUpdater($activeSourceCalculatorMock);
        $listener->preUpdate(new Supplier(), $eventArgsMock);

        $this->assertEmpty($listener->getChangedSupplierProducts());
    }

    public function testPreUpdateSkipsSupplierProductsWhenSupplierStatusChangesAndNoProducts(): void
    {
        $activeSourceCalculatorMock = $this->createMock(ActiveSourceCalculator::class);
        $eventArgsMock = $this->createMock(PreUpdateEventArgs::class);

        $eventArgsMock->method('hasChangedField')
            ->willReturnCallback(function($fieldName) {
                return $fieldName == 'isActive';
            });

        $listener = new SupplierStockUpdater($activeSourceCalculatorMock);
        $listener->preUpdate(new Supplier(), $eventArgsMock);

        $this->assertEmpty($listener->getChangedSupplierProducts());
    }

    public function testPostUpdateRecalculatesSourceForChangedProductsWithActiveSupplier(): void
    {
        $activeSourceCalculatorMock = $this->createMock(ActiveSourceCalculator::class);

        $product1 = $this->createMock(Product::class);
        $product1->method('getId')->willReturn(1);
        $product2 = $this->createMock(Product::class);
        $product2->method('getId')->willReturn(2);

        $supplierProduct1 = $this->createMock(SupplierProduct::class);
        $supplierProduct1->method('getId')->willReturn(1);
        $supplierProduct1->method('getProduct')->willReturn($product1);
        $supplierProduct2 = $this->createMock(SupplierProduct::class);
        $supplierProduct2->method('getId')->willReturn(2);
        $supplierProduct2->method('getProduct')->willReturn($product2);

        $supplier = new Supplier();
        $supplier->setIsActive(true);
        $supplier->addSupplierProduct($supplierProduct1);
        $supplier->addSupplierProduct($supplierProduct2);

        $listener = new SupplierStockUpdater($activeSourceCalculatorMock);
        $listener->setChangedSupplierProduct($supplierProduct1);
        $listener->setChangedSupplierProduct($supplierProduct2);

        $this->assertCount(2, $listener->getChangedSupplierProducts());

        $activeSourceCalculatorMock->expects($this->exactly(2))
            ->method('recalculateActiveSource')
            ->with($this->logicalOr($product1, $product2), true);

        $listener->postUpdate($supplier);
    }

    public function testPostUpdateRecalculatesSourceForChangedProductsWithInactiveSupplier(): void
    {
        $activeSourceCalculatorMock = $this->createMock(ActiveSourceCalculator::class);

        $product1 = $this->createMock(Product::class);
        $product1->method('getId')->willReturn(1);
        $product2 = $this->createMock(Product::class);
        $product2->method('getId')->willReturn(2);

        $supplierProduct1 = $this->createMock(SupplierProduct::class);
        $supplierProduct1->method('getId')->willReturn(1);
        $supplierProduct1->method('getProduct')->willReturn($product1);

        $supplierProduct2 = $this->createMock(SupplierProduct::class);
        $supplierProduct2->method('getId')->willReturn(2);
        $supplierProduct2->method('getProduct')->willReturn($product2);

        $supplier = new Supplier();
        $supplier->setIsActive(false);
        $supplier->addSupplierProduct($supplierProduct1);
        $supplier->addSupplierProduct($supplierProduct2);

        $listener = new SupplierStockUpdater($activeSourceCalculatorMock);
        $listener->setChangedSupplierProduct($supplierProduct1);
        $listener->setChangedSupplierProduct($supplierProduct2);

        $this->assertCount(2, $listener->getChangedSupplierProducts());

        $activeSourceCalculatorMock->method('getProductFromActiveSource')
            ->willReturnOnConsecutiveCalls($product1, null);

        $activeSourceCalculatorMock->expects($this->once())
            ->method('recalculateActiveSource')
            ->with($product1, false);

        $listener->postUpdate($supplier);
    }
}