<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Supplier;
use App\Entity\SupplierCategory;
use App\Entity\SupplierProduct;
use App\Entity\SupplierSubcategory;
use PHPUnit\Framework\TestCase;

class SupplierCategoryTest extends TestCase
{
    public function testSettersAndGetters(): void
    {
        $supplier = $this->createMock(Supplier::class);

        $supplierCategory = (new SupplierCategory())
            ->setName('Test Supplier Category')
            ->setSupplier($supplier);

        $this->assertEquals('Test Supplier Category', $supplierCategory->getName());
        $this->assertSame($supplier, $supplierCategory->getSupplier());
    }

    public function testAddSupplierProduct(): void
    {
        $supplierCategory = new SupplierCategory();
        $supplierProduct = $this->createMock(SupplierProduct::class);

        // Test adding a supplier product
        $supplierProduct->expects($this->once())
            ->method('setSupplierCategory')
            ->with($supplierCategory);

        $supplierCategory->addSupplierProduct($supplierProduct);
        $this->assertCount(1, $supplierCategory->getSupplierProducts());
        $this->assertTrue($supplierCategory->getSupplierProducts()->contains($supplierProduct));
    }

    public function testRemoveSupplierProduct(): void
    {
        $supplierCategory = new SupplierCategory();
        $supplierProduct = $this->createMock(SupplierProduct::class);

        // Add the supplier product first to set up the state
        $supplierCategory->addSupplierProduct($supplierProduct);

        // Test removing a supplier product
        $supplierProduct->expects($this->once())
            ->method('getSupplierCategory')
            ->willReturn($supplierCategory);

        $supplierProduct->expects($this->once())
            ->method('setSupplierCategory')
            ->with(null);

        $supplierCategory->removeSupplierProduct($supplierProduct);
        $this->assertCount(0, $supplierCategory->getSupplierProducts());
    }

    public function testAddSupplierSubcategory(): void
    {
        $supplierCategory = new SupplierCategory();
        $supplierSubcategory = $this->createMock(SupplierSubcategory::class);

        // Test adding a supplier subcategory
        $supplierSubcategory->expects($this->once())
            ->method('setSupplierCategory')
            ->with($supplierCategory);

        $supplierCategory->addSupplierSubcategory($supplierSubcategory);
        $this->assertCount(1, $supplierCategory->getSupplierSubcategories());
        $this->assertTrue($supplierCategory->getSupplierSubcategories()->contains($supplierSubcategory));
    }

    public function testRemoveSupplierSubcategory(): void
    {
        $supplierCategory = new SupplierCategory();
        $supplierSubcategory = $this->createMock(SupplierSubcategory::class);

        // Add the supplier subcategory first to set up the state
        $supplierCategory->addSupplierSubcategory($supplierSubcategory);

        // Test removing a supplier subcategory
        $supplierSubcategory->expects($this->once())
            ->method('getSupplierCategory')
            ->willReturn($supplierCategory);

        $supplierSubcategory->expects($this->once())
            ->method('setSupplierCategory')
            ->with(null);

        $supplierCategory->removeSupplierSubcategory($supplierSubcategory);
        $this->assertCount(0, $supplierCategory->getSupplierSubcategories());
    }
}