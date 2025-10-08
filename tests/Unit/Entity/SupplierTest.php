<?php

namespace App\Tests\Unit\Entity;

use App\Entity\PurchaseOrder;
use App\Entity\Supplier;
use App\Entity\SupplierCategory;
use App\Entity\SupplierManufacturer;
use App\Entity\SupplierProduct;
use App\Entity\SupplierSubcategory;
use PHPUnit\Framework\TestCase;

class SupplierTest extends TestCase
{
    public function testSettersAndGetters(): void
    {
        $supplier = new Supplier()
            ->setName('Test Supplier')
            ->setIsActive(true)
            ->setIsWarehouse(false);

        $this->assertEquals('Test Supplier', $supplier->getName());
        $this->assertTrue($supplier->isActive());
        $this->assertFalse($supplier->isWarehouse());
    }

    public function testAddSupplierCategory(): void
    {
        $supplier = new Supplier();
        $supplierCategory = $this->createMock(SupplierCategory::class);

        // Test adding a supplier category
        $supplierCategory->expects($this->once())
            ->method('setSupplier')
            ->with($supplier);

        $supplier->addSupplierCategory($supplierCategory);
        $this->assertCount(1, $supplier->getSupplierCategories());
        $this->assertTrue($supplier->getSupplierCategories()->contains($supplierCategory));
    }

    public function testRemoveSupplierCategory(): void
    {
        $supplier = new Supplier();
        $supplierCategory = $this->createMock(SupplierCategory::class);

        // Add the supplier category first to set up the state
        $supplier->addSupplierCategory($supplierCategory);

        // Test removing a supplier category
        $supplierCategory->expects($this->once())
            ->method('getSupplier')
            ->willReturn($supplier);

        $supplierCategory->expects($this->once())
            ->method('setSupplier')
            ->with(null);

        $supplier->removeSupplierCategory($supplierCategory);
        $this->assertCount(0, $supplier->getSupplierCategories());
    }

    public function testAddSupplierSubcategory(): void
    {
        $supplier = new Supplier();
        $supplierSubcategory = $this->createMock(SupplierSubcategory::class);

        // Test adding a supplier subcategory
        $supplierSubcategory->expects($this->once())
            ->method('setSupplier')
            ->with($supplier);

        $supplier->addSupplierSubcategory($supplierSubcategory);
        $this->assertCount(1, $supplier->getSupplierSubcategories());
        $this->assertTrue($supplier->getSupplierSubcategories()->contains($supplierSubcategory));
    }

    public function testRemoveSupplierSubcategory(): void
    {
        $supplier = new Supplier();
        $supplierSubcategory = $this->createMock(SupplierSubcategory::class);

        // Add the supplier subcategory first to set up the state
        $supplier->addSupplierSubcategory($supplierSubcategory);

        // Test removing a supplier subcategory
        $supplierSubcategory->expects($this->once())
            ->method('getSupplier')
            ->willReturn($supplier);

        $supplierSubcategory->expects($this->once())
            ->method('setSupplier')
            ->with(null);

        $supplier->removeSupplierSubcategory($supplierSubcategory);
        $this->assertCount(0, $supplier->getSupplierSubcategories());
    }

    public function testAddSupplierManufacturer(): void
    {
        $supplier = new Supplier();
        $supplierManufacturer = $this->createMock(SupplierManufacturer::class);

        // Test adding a supplier manufacturer
        $supplierManufacturer->expects($this->once())
            ->method('setSupplier')
            ->with($supplier);

        $supplier->addSupplierManufacturer($supplierManufacturer);
        $this->assertCount(1, $supplier->getSupplierManufacturers());
        $this->assertTrue($supplier->getSupplierManufacturers()->contains($supplierManufacturer));
    }

    public function testRemoveSupplierManufacturer(): void
    {
        $supplier = new Supplier();
        $supplierManufacturer = $this->createMock(SupplierManufacturer::class);

        // Add the supplier manufacturer first to set up the state
        $supplier->addSupplierManufacturer($supplierManufacturer);

        // Test removing a supplier manufacturer
        $supplierManufacturer->expects($this->once())
            ->method('getSupplier')
            ->willReturn($supplier);

        $supplierManufacturer->expects($this->once())
            ->method('setSupplier')
            ->with(null);

        $supplier->removeSupplierManufacturer($supplierManufacturer);
        $this->assertCount(0, $supplier->getSupplierManufacturers());
    }

    public function testAddSupplierProduct(): void
    {
        $supplier = new Supplier();
        $supplierProduct = $this->createMock(SupplierProduct::class);

        // Test adding a supplier product
        $supplierProduct->expects($this->once())
            ->method('setSupplier')
            ->with($supplier);

        $supplier->addSupplierProduct($supplierProduct);
        $this->assertCount(1, $supplier->getSupplierProducts());
        $this->assertTrue($supplier->getSupplierProducts()->contains($supplierProduct));
    }

    public function testRemoveSupplierProduct(): void
    {
        $supplier = new Supplier();
        $supplierProduct = $this->createMock(SupplierProduct::class);

        // Add the supplier product first to set up the state
        $supplier->addSupplierProduct($supplierProduct);

        // Test removing a supplier product
        $supplierProduct->expects($this->once())
            ->method('getSupplier')
            ->willReturn($supplier);

        $supplierProduct->expects($this->once())
            ->method('setSupplier')
            ->with(null);

        $supplier->removeSupplierProduct($supplierProduct);
        $this->assertCount(0, $supplier->getSupplierProducts());
    }

    public function testAddPurchaseOrder(): void
    {
        $supplier = new Supplier();
        $purchaseOrder = $this->createMock(PurchaseOrder::class);

        // Test adding a purchase order
        $purchaseOrder->expects($this->once())
            ->method('setSupplier')
            ->with($supplier);

        $supplier->addPurchaseOrder($purchaseOrder);
        $this->assertCount(1, $supplier->getPurchaseOrders());
        $this->assertTrue($supplier->getPurchaseOrders()->contains($purchaseOrder));
    }

    public function testRemovePurchaseOrder(): void
    {
        $supplier = new Supplier();
        $purchaseOrder = $this->createMock(PurchaseOrder::class);

        // Add the purchase order first to set up the state
        $supplier->addPurchaseOrder($purchaseOrder);

        // Test removing a purchase order
        $purchaseOrder->expects($this->once())
            ->method('getSupplier')
            ->willReturn($supplier);

        $supplier->removePurchaseOrder($purchaseOrder);
        $this->assertCount(0, $supplier->getPurchaseOrders());
    }
}