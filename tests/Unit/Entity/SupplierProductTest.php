<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Product;
use App\Entity\Supplier;
use App\Entity\SupplierCategory;
use App\Entity\SupplierManufacturer;
use App\Entity\SupplierSubcategory;
use PHPUnit\Framework\TestCase;
use App\Entity\SupplierProduct;

class SupplierProductTest extends TestCase
{
    public function testGetSetName(): void
    {
        $supplierProduct = new SupplierProduct();
        $supplierProduct->setName('Test SupplierProduct');

        $this->assertEquals('Test SupplierProduct', $supplierProduct->getName());
    }

    public function testGetSetProductCode(): void
    {
        $supplierProduct = new SupplierProduct();
        $supplierProduct->setProductCode('Test ProductCode');

        $this->assertEquals('Test ProductCode', $supplierProduct->getProductCode());
    }

    public function testGetSetSupplierCategory(): void
    {
        $supplierCategory = $this->createMock(SupplierCategory::class);
        $supplierCategory->method('getName')->willReturn('Test SupplierCategory');

        $supplierProduct = new SupplierProduct();
        $supplierProduct->setSupplierCategory($supplierCategory);

        $this->assertEquals('Test SupplierCategory', $supplierProduct->getSupplierCategory()->getName());
    }

    public function testGetSetSupplierSubcategory(): void
    {
        $supplierSubcategory = $this->createMock(SupplierSubcategory::class);
        $supplierSubcategory->method('getName')->willReturn('Test SupplierSubcategory');

        $supplierProduct = new SupplierProduct();
        $supplierProduct->setSupplierSubcategory($supplierSubcategory);

        $this->assertEquals('Test SupplierSubcategory', $supplierProduct->getSupplierSubcategory()->getName());
    }

    public function testGetSetSupplierManufacturer(): void
    {
        $supplierManufacturer = $this->createMock(SupplierManufacturer::class);
        $supplierManufacturer->method('getName')->willReturn('Test SupplierManufacturer');

        $supplierProduct = new SupplierProduct();
        $supplierProduct->setSupplierManufacturer($supplierManufacturer);

        $this->assertEquals('Test SupplierManufacturer', $supplierProduct->getSupplierManufacturer()->getName());
    }

    public function testGetSetMfrPartNumber(): void
    {
        $supplierProduct = new SupplierProduct();
        $supplierProduct->setMfrPartNumber('Test MfrPartNumber');

        $this->assertEquals('Test MfrPartNumber', $supplierProduct->getMfrPartNumber());
    }

    public function testGetSetWeight(): void
    {
        $supplierProduct = new SupplierProduct();
        $supplierProduct->setWeight(500);

        $this->assertEquals(500, $supplierProduct->getWeight());
    }

    public function testGetSetStock(): void
    {
        $supplierProduct = new SupplierProduct();
        $supplierProduct->setStock(10);

        $this->assertEquals(10, $supplierProduct->getStock());
    }

    public function testGetSetLeadTimeDays(): void
    {
        $supplierProduct = new SupplierProduct();
        $supplierProduct->setLeadTimeDays(10);

        $this->assertEquals(10, $supplierProduct->getLeadTimeDays());
    }

    public function testGetSetCost(): void
    {
        $supplierProduct = new SupplierProduct();
        $supplierProduct->setCost(10.5);

        $this->assertEquals(10.5, $supplierProduct->getCost());
    }

    public function testGetSetSupplier(): void
    {
        $supplier = $this->createMock(Supplier::class);
        $supplier->method('getName')->willReturn('Test Supplier');

        $supplierProduct = new SupplierProduct();
        $supplierProduct->setSupplier($supplier);

        $this->assertEquals('Test Supplier', $supplierProduct->getSupplier()->getName());
    }

    public function testGetSetProduct(): void
    {
        $product = $this->createMock(Product::class);
        $product->method('getName')->willReturn('Test Product');

        $supplierProduct = new SupplierProduct();
        $supplierProduct->setProduct($product);

        $this->assertEquals('Test Product', $supplierProduct->getProduct()->getName());
    }

    public function testGetSetIsActive(): void
    {
        $supplierProduct = new SupplierProduct();
        $supplierProduct->setIsActive(true);

        $this->assertTrue($supplierProduct->IsisActive());
    }
}