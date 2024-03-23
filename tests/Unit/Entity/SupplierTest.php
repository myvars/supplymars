<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Supplier;
use App\Entity\SupplierCategory;
use App\Entity\SupplierManufacturer;
use App\Entity\SupplierProduct;
use App\Entity\SupplierSubcategory;
use PHPUnit\Framework\TestCase;

class SupplierTest extends TestCase
{
    public function testGetSetName(): void
    {
        $supplier = new Supplier();
        $supplier->setName('Test Supplier');

        $this->assertEquals('Test Supplier', $supplier->getName());
    }

    public function testGetSetIsActive(): void
    {
        $supplier = new Supplier();
        $supplier->setIsActive(true);

        $this->assertTrue($supplier->isIsActive());
    }

    public function testAddRemoveSupplierCategory(): void
    {
        $supplierCategory = $this->createMock(SupplierCategory::class);
        $supplier = new Supplier();
        $supplier->addSupplierCategory($supplierCategory);

        $this->assertEquals($supplierCategory, $supplier->getSupplierCategories()->first());

        $supplier->removeSupplierCategory($supplierCategory);

        $this->assertEmpty($supplier->getSupplierCategories());
    }

    public function testAddRemoveSupplierSubcategory(): void
    {
        $supplierSubcategory = $this->createMock(SupplierSubcategory::class);
        $supplier = new Supplier();
        $supplier->addSupplierSubcategory($supplierSubcategory);

        $this->assertEquals($supplierSubcategory, $supplier->getSupplierSubcategories()->first());

        $supplier->removeSupplierSubcategory($supplierSubcategory);

        $this->assertEmpty($supplier->getSupplierSubcategories());
    }

    public function testAddRemoveSupplierManufacturer(): void
    {
        $supplierManufacturer = $this->createMock(SupplierManufacturer::class);
        $supplier = new Supplier();
        $supplier->addSupplierManufacturer($supplierManufacturer);

        $this->assertEquals($supplierManufacturer, $supplier->getSupplierManufacturers()->first());

        $supplier->removeSupplierManufacturer($supplierManufacturer);

        $this->assertEmpty($supplier->getSupplierManufacturers());
    }

    public function testAddRemoveSupplierProduct(): void
    {
        $supplier = new Supplier();
        $supplierProduct = $this->createMock(SupplierProduct::class);
        $supplier->addSupplierProduct($supplierProduct);

        $this->assertEquals($supplierProduct, $supplier->getSupplierProducts()->first());

        $supplier->removeSupplierProduct($supplierProduct);

        $this->assertEmpty($supplier->getSupplierProducts());
    }
}