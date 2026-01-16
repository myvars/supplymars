<?php

namespace App\Tests\Catalog\Unit;

use App\Catalog\Domain\Model\Manufacturer\Manufacturer;
use App\Catalog\Domain\Model\Product\Product;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierManufacturer;
use PHPUnit\Framework\TestCase;

class ManufacturerTest extends TestCase
{
    public function testSettersAndGetters(): void
    {
        $manufacturer = new Manufacturer()
            ->setName('Test Manufacturer')
            ->setIsActive(true);

        $this->assertEquals('Test Manufacturer', $manufacturer->getName());
        $this->assertTrue($manufacturer->isActive());
    }

    public function testAddProduct(): void
    {
        $manufacturer = new Manufacturer();
        $product = $this->createMock(Product::class);

        // Test adding a product
        $product->expects($this->once())
            ->method('assignManufacturer')
            ->with($manufacturer);

        $manufacturer->addProduct($product);
        $this->assertCount(1, $manufacturer->getProducts());
        $this->assertTrue($manufacturer->getProducts()->contains($product));
    }

    public function testRemoveProduct(): void
    {
        $manufacturer = new Manufacturer();
        $product = $this->createMock(Product::class);

        // Add the product first to set up the state
        $manufacturer->addProduct($product);

        // Test removing a product
        $product->expects($this->once())
            ->method('getManufacturer')
            ->willReturn($manufacturer);

        $product->expects($this->once())
            ->method('assignManufacturer')
            ->with(null);

        $manufacturer->removeProduct($product);
        $this->assertCount(0, $manufacturer->getProducts());
    }

    public function testAddSupplierManufacturer(): void
    {
        $manufacturer = new Manufacturer();
        $supplierManufacturer = $this->createMock(SupplierManufacturer::class);

        // Test adding a supplier manufacturer
        $supplierManufacturer->expects($this->once())
            ->method('assignMappedManufacturer')
            ->with($manufacturer);

        $manufacturer->addSupplierManufacturer($supplierManufacturer);
        $this->assertCount(1, $manufacturer->getSupplierManufacturers());
        $this->assertTrue($manufacturer->getSupplierManufacturers()->contains($supplierManufacturer));
    }

    public function testRemoveSupplierManufacturer(): void
    {
        $manufacturer = new Manufacturer();
        $supplierManufacturer = $this->createMock(SupplierManufacturer::class);

        // Add the supplier manufacturer first to set up the state
        $manufacturer->addSupplierManufacturer($supplierManufacturer);

        // Test removing a supplier manufacturer
        $supplierManufacturer->expects($this->once())
            ->method('getMappedManufacturer')
            ->willReturn($manufacturer);

        $supplierManufacturer->expects($this->once())
            ->method('assignMappedManufacturer')
            ->with(null);

        $manufacturer->removeSupplierManufacturer($supplierManufacturer);
        $this->assertCount(0, $manufacturer->getSupplierManufacturers());
    }
}
