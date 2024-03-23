<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Category;
use App\Entity\Manufacturer;
use App\Entity\PriceModel;
use App\Entity\Product;
use App\Entity\ProductImage;
use App\Entity\Subcategory;
use App\Entity\SupplierProduct;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class ProductTest extends TestCase
{
    public function testGetSetName(): void
    {
        $product = new Product();
        $product->setName('Test Product');

        $this->assertEquals('Test Product', $product->getName());
    }

    public function testGetSetMfrPartNumber(): void
    {
        $product = new Product();
        $product->setMfrPartNumber('Test MfrPartNumber');

        $this->assertEquals('Test MfrPartNumber', $product->getMfrPartNumber());
    }

    public function testGetSetStock(): void
    {
        $product = new Product();
        $product->setStock(10);

        $this->assertEquals(10, $product->getStock());
    }

    public function testGetSetLeadTimeDays(): void
    {
        $product = new Product();
        $product->setLeadTimeDays(10);

        $this->assertEquals(10, $product->getLeadTimeDays());
    }

    public function testGetSetWeight(): void
    {
        $product = new Product();
        $product->setWeight(500);

        $this->assertEquals(500, $product->getWeight());
    }

    public function testGetSetDefaultMarkup(): void
    {
        $product = new Product();
        $product->setDefaultMarkup(10);

        $this->assertEquals(10, $product->getDefaultMarkup());
    }

    public function testGetSetMarkup(): void
    {
        $product = new Product();
        $product->setMarkup(10);

        $this->assertEquals(10, $product->getMarkup());
    }

    public function testGetSetCost(): void
    {
        $product = new Product();
        $product->setCost(10.5);

        $this->assertEquals(10.5, $product->getCost());
    }

    public function testGetSetSellPrice(): void
    {
        $product = new Product();
        $product->setSellPrice(10.5);

        $this->assertEquals(10.5, $product->getSellPrice());
    }

    public function testGetSetSellPriceIncVat(): void
    {
        $product = new Product();
        $product->setSellPriceIncVat(10.5);

        $this->assertEquals(10.5, $product->getSellPriceIncVat());
    }

    public function testGetSetCategory(): void
    {
        $category = $this->createMock(Category::class);
        $category->method('getName')->willReturn('Test Category');

        $product = new Product();
        $product->setCategory($category);

        $this->assertEquals('Test Category', $product->getCategory()->getName());
    }

    public function testGetSetSubcategory(): void
    {
        $subcategory = $this->createMock(Subcategory::class);
        $subcategory->method('getName')->willReturn('Test Subcategory');

        $product = new Product();
        $product->setSubcategory($subcategory);

        $this->assertEquals('Test Subcategory', $product->getSubcategory()->getName());
    }

    public function testGetSetManufacturer(): void
    {
        $manufacturer = $this->createMock(Manufacturer::class);
        $manufacturer->method('getName')->willReturn('Test Manufacturer');

        $product = new Product();
        $product->setManufacturer($manufacturer);

        $this->assertEquals('Test Manufacturer', $product->getManufacturer()->getName());
    }

    public function testGetSetOwner(): void
    {
        $owner = $this->createMock(User::class);
        $owner->method('getFullName')->willReturn('Test Owner');

        $product = new Product();
        $product->setOwner($owner);

        $this->assertEquals('Test Owner', $product->getOwner()->getFullName());
    }

    public function testGetSetPriceModal(): void
    {
        $priceModel = PriceModel::PRETTY_99;

        $product = new Product();
        $product->setPriceModel($priceModel);

        $this->assertEquals('Pretty 99', $product->getPriceModel()->getName());
    }

    public function testGetSetActiveProductSource(): void
    {
        $supplierProduct = $this->createMock(SupplierProduct::class);
        $supplierProduct->method('getName')->willReturn('Test SupplierProduct');

        $product = new Product();
        $product->setActiveProductSource($supplierProduct);

        $this->assertEquals('Test SupplierProduct', $product->getActiveProductSource()->getName());
    }

    public function testGetSetIsActive(): void
    {
        $product = new Product();
        $product->setIsActive(true);

        $this->assertTrue($product->IsisActive());
    }

    public function testGetActiveMarkupAndTarget(): void
    {
        $category = $this->createMock(Category::class);
        $category->method('getDefaultMarkup')->willReturn('2.000');

        $subcategory = $this->createMock(Subcategory::class);
        $subcategory->method('getDefaultMarkup')->willReturn('5.000');

        $resetSubcategory = $this->createMock(Subcategory::class);
        $resetSubcategory->method('getDefaultMarkup')->willReturn('0.000');

        $product = new Product();
        $product->setCategory($category);
        $product->setSubcategory($subcategory);
        $product->setDefaultMarkup('10.000');

        $this->assertEquals('10.000', $product->getActiveMarkup());
        $this->assertEquals('product', $product->getActiveMarkupTarget());

        $product->setDefaultMarkup('0.000');
        $this->assertEquals('5.000', $product->getActiveMarkup());
        $this->assertEquals('subcategory', $product->getActiveMarkupTarget());

        $product->setSubcategory($resetSubcategory);
        $this->assertEquals('2.000', $product->getActiveMarkup());
        $this->assertEquals('category', $product->getActiveMarkupTarget());
    }

    public function testGetActivePriceModelAndTargetFromProduct(): void
    {
        $category = $this->createMock(Category::class);
        $category->method('getPriceModel')->willReturn(PriceModel::PRETTY_99);

        $subcategory = $this->createMock(Subcategory::class);
        $subcategory->method('getPriceModel')->willReturn(PriceModel::PRETTY_95);

        $product = new Product();
        $product->setCategory($category);
        $product->setSubcategory($subcategory);
        $product->setPriceModel(PriceModel::PRETTY_00);

        $this->assertEquals(PriceModel::PRETTY_00, $product->getActivePriceModel());
        $this->assertEquals('product', $product->getActivePriceModelTarget());
    }

    public function testGetActivePriceModelAndTargetFromSubcategory(): void
    {
        $category = $this->createMock(Category::class);
        $category->method('getPriceModel')->willReturn(PriceModel::PRETTY_99);

        $subcategory = $this->createMock(Subcategory::class);
        $subcategory->method('getPriceModel')->willReturn(PriceModel::PRETTY_95);

        $product = new Product();
        $product->setCategory($category);
        $product->setSubcategory($subcategory);
        $product->setPriceModel(PriceModel::NONE);

        $this->assertEquals(PriceModel::PRETTY_95, $product->getActivePriceModel());
        $this->assertEquals('subcategory', $product->getActivePriceModelTarget());
    }

    public function testGetActivePriceModelAndTargetFromCategory(): void
    {
        $category = $this->createMock(Category::class);
        $category->method('getPriceModel')->willReturn(PriceModel::PRETTY_99);

        $subcategory = $this->createMock(Subcategory::class);
        $subcategory->method('getPriceModel')->willReturn(PriceModel::NONE);

        $product = new Product();
        $product->setCategory($category);
        $product->setSubcategory($subcategory);
        $product->setPriceModel(PriceModel::NONE);

        $this->assertEquals(PriceModel::PRETTY_99, $product->getActivePriceModel());
        $this->assertEquals('category', $product->getActivePriceModelTarget());
    }

    public function testAddRemoveSupplierProduct(): void
    {
        $product = new Product();
        $supplierProduct = $this->createMock(SupplierProduct::class);

        $product->addSupplierProduct($supplierProduct);
        $this->assertEquals($supplierProduct, $product->getSupplierProducts()->first());

        $product->removeSupplierProduct($supplierProduct);
        $this->assertEmpty($product->getSupplierProducts());
    }

    public function testAddRemoveProductImage(): void
    {
        $product = new Product();
        $productImage = $this->createMock(ProductImage::class);

        $product->addProductImage($productImage);
        $this->assertEquals($productImage, $product->getProductImages()->first());

        $product->removeProductImage($productImage);
        $this->assertEmpty($product->getProductImages());
    }
}