<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Supplier;
use App\Entity\SupplierCategory;
use App\Entity\SupplierProduct;
use App\Entity\SupplierSubcategory;
use PHPUnit\Framework\TestCase;

class SupplierCategoryTest extends TestCase
{
    public function testGetSetName(): void
    {
        $supplierCategory = new SupplierCategory();
        $supplierCategory->setName('Test Supplier Category');

        $this->assertEquals('Test Supplier Category', $supplierCategory->getName());
    }

    public function testGetSetSupplier(): void
    {
        $supplier = $this->createMock(Supplier::class);
        $supplierCategory = new SupplierCategory();
        $supplierCategory->setSupplier($supplier);

        $this->assertEquals($supplier, $supplierCategory->getSupplier());
    }

    public function testAddRemoveSupplierProduct(): void
    {
        $supplierProduct = $this->createMock(SupplierProduct::class);
        $supplierCategory = new SupplierCategory();
        $supplierCategory->addSupplierProduct($supplierProduct);

        $this->assertEquals($supplierProduct, $supplierCategory->getSupplierProducts()->first());

        $supplierCategory->removeSupplierProduct($supplierProduct);

        $this->assertEmpty($supplierCategory->getSupplierProducts());
    }

    public function testAddRemoveSupplierSubcategory(): void
    {
        $supplierSubcategory = $this->createMock(SupplierSubcategory::class);
        $supplierCategory = new SupplierCategory();
        $supplierCategory->addSupplierSubcategory($supplierSubcategory);

        $this->assertEquals($supplierSubcategory, $supplierCategory->getSupplierSubcategories()->first());

        $supplierCategory->removeSupplierSubcategory($supplierSubcategory);

        $this->assertEmpty($supplierCategory->getSupplierSubcategories());
    }
}