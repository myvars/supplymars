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
    public function testGetSetName(): void
    {
        $supplierSubcategory = new SupplierSubcategory();
        $supplierSubcategory->setName('Test Supplier Subcategory');

        $this->assertEquals('Test Supplier Subcategory', $supplierSubcategory->getName());
    }

    public function testGetSetSupplier(): void
    {
        $supplier = $this->createMock(Supplier::class);
        $supplierSubcategory = new SupplierSubcategory();
        $supplierSubcategory->setSupplier($supplier);

        $this->assertEquals($supplier, $supplierSubcategory->getSupplier());
    }

    public function testGetSetSupplierCategory(): void
    {
        $supplierCategory = $this->createMock(SupplierCategory::class);
        $supplierSubcategory = new SupplierSubcategory();
        $supplierSubcategory->setSupplierCategory($supplierCategory);

        $this->assertEquals($supplierCategory, $supplierSubcategory->getSupplierCategory());
    }

    public function testGetSetMappedSubcategory(): void
    {
        $subcategory = $this->createMock(Subcategory::class);
        $supplierSubcategory = new SupplierSubcategory();
        $supplierSubcategory->setMappedSubcategory($subcategory);

        $this->assertEquals($subcategory, $supplierSubcategory->getMappedSubcategory());
    }

    public function testAddRemoveSupplierProduct(): void
    {
        $supplierProduct = $this->createMock(SupplierProduct::class);
        $supplierSubcategory = new SupplierSubcategory();
        $supplierSubcategory->addSupplierProduct($supplierProduct);

        $this->assertEquals($supplierProduct, $supplierSubcategory->getSupplierProducts()->first());

        $supplierSubcategory->removeSupplierProduct($supplierProduct);

        $this->assertEmpty($supplierSubcategory->getSupplierProducts());
    }
}