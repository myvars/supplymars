<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Supplier;
use App\Entity\SupplierManufacturer;
use App\Entity\SupplierProduct;
use PHPUnit\Framework\TestCase;

class SupplierManufacturerTest extends TestCase
{
    public function testGetSetName(): void
    {
        $supplierManufacturer = new SupplierManufacturer();
        $supplierManufacturer->setName('Test Supplier Manufacturer');

        $this->assertEquals('Test Supplier Manufacturer', $supplierManufacturer->getName());
    }

    public function testGetSetSupplier(): void
    {
        $supplier = $this->createMock(Supplier::class);
        $supplierManufacturer = new SupplierManufacturer();
        $supplierManufacturer->setSupplier($supplier);

        $this->assertEquals($supplier, $supplierManufacturer->getSupplier());
    }

    public function testAddRemoveSupplierProduct(): void
    {
        $supplierProduct = $this->createMock(SupplierProduct::class);
        $supplierManufacturer = new SupplierManufacturer();
        $supplierManufacturer->addSupplierProduct($supplierProduct);

        $this->assertEquals($supplierProduct, $supplierManufacturer->getSupplierProducts()->first());

        $supplierManufacturer->removeSupplierProduct($supplierProduct);

        $this->assertEmpty($supplierManufacturer->getSupplierProducts());
    }
}