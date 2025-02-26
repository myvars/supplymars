<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Subcategory;
use App\Entity\Supplier;
use App\Entity\SupplierCategory;
use App\Entity\SupplierProduct;
use App\Entity\SupplierSubcategory;
use PHPUnit\Framework\TestCase;

class SupplierSubcategoryTest extends TestCase
{
    public function testSettersAndGetters(): void
    {
        $supplier = $this->createMock(Supplier::class);
        $supplierCategory = $this->createMock(SupplierCategory::class);

        $supplierSubcategory = (new SupplierSubcategory())
            ->setName('Test Supplier Subcategory')
            ->setSupplier($supplier)
            ->setSupplierCategory($supplierCategory);

        $this->assertEquals('Test Supplier Subcategory', $supplierSubcategory->getName());
        $this->assertSame($supplier, $supplierSubcategory->getSupplier());
        $this->assertSame($supplierCategory, $supplierSubcategory->getSupplierCategory());
    }

    public function testAddSupplierProduct(): void
    {
        $supplierSubcategory = new SupplierSubcategory();
        $supplierProduct = $this->createMock(SupplierProduct::class);

        // Test adding a supplier product
        $supplierProduct->expects($this->once())
            ->method('setSupplierSubcategory')
            ->with($supplierSubcategory);

        $supplierSubcategory->addSupplierProduct($supplierProduct);
        $this->assertCount(1, $supplierSubcategory->getSupplierProducts());
        $this->assertTrue($supplierSubcategory->getSupplierProducts()->contains($supplierProduct));
    }

    public function testRemoveSupplierProduct(): void
    {
        $supplierSubcategory = new SupplierSubcategory();
        $supplierProduct = $this->createMock(SupplierProduct::class);

        // Add the supplier product first to set up the state
        $supplierSubcategory->addSupplierProduct($supplierProduct);

        // Test removing a supplier product
        $supplierProduct->expects($this->once())
            ->method('getSupplierSubcategory')
            ->willReturn($supplierSubcategory);

        $supplierProduct->expects($this->once())
            ->method('setSupplierSubcategory')
            ->with(null);

        $supplierSubcategory->removeSupplierProduct($supplierProduct);
        $this->assertCount(0, $supplierSubcategory->getSupplierProducts());
    }

    public function testGetMappedSubcategory(): void
    {
        $supplierSubcategory = new SupplierSubcategory();
        $subcategory = $this->createMock(Subcategory::class);

        $supplierSubcategory->setMappedSubcategory($subcategory);

        $this->assertSame($subcategory, $supplierSubcategory->getMappedSubcategory());
    }

    public function testSetMappedSubcategory(): void
    {
        $supplierSubcategory = new SupplierSubcategory();
        $subcategory = $this->createMock(Subcategory::class);

        $supplierSubcategory->setMappedSubcategory($subcategory);

        $this->assertSame($subcategory, $supplierSubcategory->getMappedSubcategory());
    }


}