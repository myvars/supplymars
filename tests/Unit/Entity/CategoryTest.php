<?php

namespace App\Tests\Unit\Entity;

use App\Entity\PriceModel;
use App\Entity\Product;
use App\Entity\Subcategory;
use App\Entity\User;
use App\Entity\VatRate;
use PHPUnit\Framework\TestCase;
use App\Entity\Category;

class CategoryTest extends TestCase
{
    public function testGetSetName(): void
    {
        $category = new Category();
        $category->setName('Test Category');

        $this->assertEquals('Test Category', $category->getName());
    }

    public function testGetSetDefaultMarkup(): void
    {
        $category = new Category();
        $category->setDefaultMarkup(0.21);

        $this->assertEquals(0.21, $category->getDefaultMarkup());
    }

    public function testGetSetOwner(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getFullName')->willReturn('Test Owner');

        $category = new Category();
        $category->setOwner($user);

        $this->assertEquals('Test Owner', $category->getOwner()->getFullName());
    }

    public function testGetSetVatRate(): void
    {
        $vatRate = $this->createMock(VatRate::class);
        $vatRate->method('getName')->willReturn('Test VatRate');

        $category = new Category();
        $category->setVatRate($vatRate);

        $this->assertEquals('Test VatRate', $category->getVatRate()->getName());
    }

    public function testGetSetPriceModel(): void
    {
        $priceModel = PriceModel::PRETTY_99;

        $category = new Category();
        $category->setPriceModel($priceModel);

        $this->assertEquals('Pretty 99', $category->getPriceModel()->getName());
    }

    public function testGetSetIsActive(): void
    {
        $category = new Category();
        $category->setIsActive(true);

        $this->assertTrue($category->isActive());
    }

    public function testAddRemoveSubcategory(): void
    {
        $subcategory = $this->createMock(Subcategory::class);
        $category = new Category();
        $category->addSubcategory($subcategory);

        $this->assertEquals($subcategory, $category->getSubcategories()->first());

        $category->removeSubcategory($subcategory);

        $this->assertEmpty($category->getSubcategories());
    }

    public function testAddRemoveProduct(): void
    {
        $product = $this->createMock(Product::class);
        $category = new Category();
        $category->addProduct($product);

        $this->assertEquals($product, $category->getProducts()->first());

        $category->removeProduct($product);

        $this->assertEmpty($category->getProducts());
    }
}