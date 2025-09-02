<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Product;
use App\Entity\Supplier;
use App\Entity\SupplierCategory;
use App\Entity\SupplierManufacturer;
use App\Entity\SupplierProduct;
use App\Entity\SupplierSubcategory;
use App\Event\SupplierProductCostWasChangedEvent;
use App\Event\SupplierProductStockWasChangedEvent;
use PHPUnit\Framework\TestCase;

class SupplierProductTest extends TestCase
{
    public function testSettersAndGetters(): void
    {
        $supplier = $this->createMock(Supplier::class);
        $supplierCategory = $this->createMock(SupplierCategory::class);
        $supplierSubcategory = $this->createMock(SupplierSubcategory::class);
        $supplierManufacturer = $this->createMock(SupplierManufacturer::class);
        $product = $this->createMock(Product::class);

        $supplierProduct = (new SupplierProduct())
            ->setName('Test Supplier Product')
            ->setProductCode('TP12345')
            ->setSupplier($supplier)
            ->setSupplierCategory($supplierCategory)
            ->setSupplierSubcategory($supplierSubcategory)
            ->setSupplierManufacturer($supplierManufacturer)
            ->setMfrPartNumber('PART-1234')
            ->setWeight(5000)
            ->setStock(100)
            ->setLeadTimeDays(10)
            ->setCost('150.00')
            ->setProduct($product)
            ->setIsActive(true);

        $this->assertEquals('Test Supplier Product', $supplierProduct->getName());
        $this->assertEquals('TP12345', $supplierProduct->getProductCode());
        $this->assertSame($supplier, $supplierProduct->getSupplier());
        $this->assertSame($supplierCategory, $supplierProduct->getSupplierCategory());
        $this->assertSame($supplierSubcategory, $supplierProduct->getSupplierSubcategory());
        $this->assertSame($supplierManufacturer, $supplierProduct->getSupplierManufacturer());
        $this->assertEquals('PART-1234', $supplierProduct->getMfrPartNumber());
        $this->assertEquals(5000, $supplierProduct->getWeight());
        $this->assertEquals(100, $supplierProduct->getStock());
        $this->assertEquals(10, $supplierProduct->getLeadTimeDays());
        $this->assertEquals('150.00', $supplierProduct->getCost());
        $this->assertSame($product, $supplierProduct->getProduct());
        $this->assertTrue($supplierProduct->isActive());
    }

    public function testIsMapped(): void
    {
        $supplierProduct = new SupplierProduct();
        $this->assertFalse($supplierProduct->isMapped());

        $product = $this->createMock(Product::class);
        $supplierProduct->setProduct($product);
        $this->assertTrue($supplierProduct->isMapped());
    }

    public function testHasActiveSupplier(): void
    {
        $supplier = $this->createMock(Supplier::class);
        $supplier->method('isActive')->willReturn(true);

        $supplierProduct = new SupplierProduct();
        $supplierProduct->setSupplier($supplier);

        $this->assertTrue($supplierProduct->hasActiveSupplier());
    }

    public function testHasStock(): void
    {
        $supplierProduct = new SupplierProduct();
        $supplierProduct->setStock(0);
        $this->assertFalse($supplierProduct->hasStock());

        $supplierProduct->setStock(10);
        $this->assertTrue($supplierProduct->hasStock());
    }

    public function testSetStockRaisesEvent(): void
    {
        $supplierProduct = new SupplierProduct();
        $supplierProduct->setStock(10);

        $events = $supplierProduct->releaseDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(SupplierProductStockWasChangedEvent::class, $events[0]);
        $this->assertSame($supplierProduct, $events[0]->getSupplierProduct());
    }

    public function testSetCostRaisesEvent(): void
    {
        $supplierProduct = new SupplierProduct();
        $supplierProduct->setCost('100.00');

        $events = $supplierProduct->releaseDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(SupplierProductStockWasChangedEvent::class, $events[0]);
        $this->assertSame($supplierProduct, $events[0]->getSupplierProduct());
    }

    public function testSetCostDoesNotRaiseEventWhenCostIsSame(): void
    {
        $supplierProduct = new SupplierProduct();
        $supplierProduct->setCost('100.00');
        $supplierProduct->releaseDomainEvents();

        // Set the same cost again
        $supplierProduct->setCost('100.00');

        $events = $supplierProduct->releaseDomainEvents();
        $this->assertCount(0, $events);
    }
}
