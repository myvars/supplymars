<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Category;
use App\Entity\PriceModel;
use App\Entity\Product;
use App\Entity\Subcategory;
use App\Entity\SupplierSubcategory;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class SubcategoryTest extends TestCase
{
    public function testGetSetName(): void
    {
        $subcategory = new Subcategory();
        $subcategory->setName('Test Subcategory');

        $this->assertEquals('Test Subcategory', $subcategory->getName());
    }

    public function testGetSetCategory(): void
    {
        $category = $this->createMock(Category::class);
        $category->method('getName')->willReturn('Test Category');

        $subcategory = new Subcategory();
        $subcategory->setCategory($category);

        $this->assertEquals('Test Category', $subcategory->getCategory()->getName());
    }

    public function testGetSetDefaultMarkup(): void
    {
        $subcategory = new Subcategory();
        $subcategory->setDefaultMarkup(0.21);

        $this->assertEquals(0.21, $subcategory->getDefaultMarkup());
    }

    public function testGetSetOwner(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getFullName')->willReturn('Test Owner');

        $subcategory = new Subcategory();
        $subcategory->setOwner($user);

        $this->assertEquals('Test Owner', $subcategory->getOwner()->getFullName());
    }

    public function testGetSetPriceModel(): void
    {
        $priceModel = PriceModel::PRETTY_99;

        $subcategory = new Subcategory();
        $subcategory->setPriceModel($priceModel);

        $this->assertEquals('Pretty 99', $subcategory->getPriceModel()->getName());
    }

    public function testGetSetIsActive(): void
    {
        $subcategory = new Subcategory();
        $subcategory->setIsActive(true);

        $this->assertTrue($subcategory->isIsActive());
    }

    public function testAddRemoveProduct(): void
    {
        $product = $this->createMock(Product::class);
        $subcategory = new Subcategory();
        $subcategory->addProduct($product);

        $this->assertEquals($product, $subcategory->getProducts()->first());

        $subcategory->removeProduct($product);

        $this->assertEmpty($subcategory->getProducts());
    }

    public function testAddRemoveSupplierSubcategory(): void
    {
        $supplierSubcategory = $this->createMock(SupplierSubcategory::class);
        $subcategory = new Subcategory();
        $subcategory->addSupplierSubcategory($supplierSubcategory);

        $this->assertEquals($supplierSubcategory, $subcategory->getSupplierSubcategories()->first());

        $subcategory->removeSupplierSubcategory($supplierSubcategory);

        $this->assertEmpty($subcategory->getSupplierSubcategories());
    }
}