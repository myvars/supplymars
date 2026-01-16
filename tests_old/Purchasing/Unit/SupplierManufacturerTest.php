<?php

namespace App\Tests\Purchasing\Unit;

use App\Purchasing\Domain\Model\Supplier\Supplier;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierManufacturer;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProduct;
use PHPUnit\Framework\TestCase;

class SupplierManufacturerTest extends TestCase
{
    public function testSettersAndGetters(): void
    {
        $supplier = $this->createMock(Supplier::class);

        $supplierManufacturer = new SupplierManufacturer()
            ->setName('Test Supplier Manufacturer')
            ->setSupplier($supplier);

        $this->assertEquals('Test Supplier Manufacturer', $supplierManufacturer->getName());
        $this->assertSame($supplier, $supplierManufacturer->getSupplier());
    }

    public function testAddSupplierProduct(): void
    {
        $supplierManufacturer = new SupplierManufacturer();
        $supplierProduct = $this->createMock(SupplierProduct::class);

        // Test adding a supplier product
        $supplierProduct->expects($this->once())
            ->method('setSupplierManufacturer')
            ->with($supplierManufacturer);

        $supplierManufacturer->addSupplierProduct($supplierProduct);
        $this->assertCount(1, $supplierManufacturer->getSupplierProducts());
        $this->assertTrue($supplierManufacturer->getSupplierProducts()->contains($supplierProduct));
    }

    public function testRemoveSupplierProduct(): void
    {
        $supplierManufacturer = new SupplierManufacturer();
        $supplierProduct = $this->createMock(SupplierProduct::class);

        // Add the supplier product first to set up the state
        $supplierManufacturer->addSupplierProduct($supplierProduct);

        // Test removing a supplier product
        $supplierProduct->expects($this->once())
            ->method('getSupplierManufacturer')
            ->willReturn($supplierManufacturer);

        $supplierProduct->expects($this->once())
            ->method('setSupplierManufacturer')
            ->with(null);

        $supplierManufacturer->removeSupplierProduct($supplierProduct);
        $this->assertCount(0, $supplierManufacturer->getSupplierProducts());
    }
}
