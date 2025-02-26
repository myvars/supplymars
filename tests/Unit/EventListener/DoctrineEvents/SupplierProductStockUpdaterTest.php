<?php

namespace App\Tests\Unit\EventListener\DoctrineEvents;


use App\Entity\Product;
use App\Entity\SupplierProduct;
use App\EventListener\DoctrineEvents\SupplierProductStockUpdater;
use App\Service\Product\ActiveSourceCalculator;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use PHPUnit\Framework\TestCase;

class SupplierProductStockUpdaterTest extends TestCase
{
    public function testPreUpdateIdentifiesSupplierProductsWhenSupplierProductChanges(): void
    {
        $activeSourceCalculatorMock = $this->createMock(ActiveSourceCalculator::class);
        $eventArgsMock = $this->createMock(PreUpdateEventArgs::class);

        $supplierProduct = $this->createMock(SupplierProduct::class);
        $supplierProduct->method('getId')->willReturn(1);

        $eventArgsMock->method('hasChangedField')
            ->willReturnCallback(fn($fieldName): bool => $fieldName == 'isActive');

        $listener = new SupplierProductStockUpdater($activeSourceCalculatorMock);
        $listener->preUpdate($supplierProduct, $eventArgsMock);

        $this->assertArrayHasKey(1, $listener->getChangedSupplierProducts());
    }

    public function testPreUpdateSkipsSupplierProductsWhenSupplierProductDoesNotChange(): void
    {
        $activeSourceCalculatorMock = $this->createMock(ActiveSourceCalculator::class);
        $eventArgsMock = $this->createMock(PreUpdateEventArgs::class);

        $eventArgsMock->method('hasChangedField')
            ->willReturnCallback(fn($fieldName): bool => $fieldName == 'none');

        $listener = new SupplierProductStockUpdater($activeSourceCalculatorMock);
        $listener->preUpdate(new SupplierProduct(), $eventArgsMock);

        $this->assertEmpty($listener->getChangedSupplierProducts());
    }

    public function testPostUpdateRecalculatesSourceForChangedSupplierProducts(): void
    {
        $activeSourceCalculatorMock = $this->createMock(ActiveSourceCalculator::class);

        $product1 = $this->createMock(Product::class);
        $product1->method('getId')->willReturn(1);

        $supplierProduct = new SupplierProduct();
        $supplierProduct->setProduct($product1);

        $listener = new SupplierProductStockUpdater($activeSourceCalculatorMock);
        $listener->setChangedSupplierProduct($supplierProduct);
        $listener->setChangedProduct($product1);

        $this->assertCount(1, $listener->getChangedSupplierProducts());

        $activeSourceCalculatorMock->expects($this->once())
            ->method('getProductFromActiveSource')
            ->with($supplierProduct)
            ->willReturn(null);

        $activeSourceCalculatorMock->expects($this->once())
            ->method('recalculateActiveSourceFromArray')
            ->with($listener->getChangedProducts());

        $listener->postUpdate($supplierProduct);
    }
}