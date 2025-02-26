<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\Subcategory;
use App\Entity\User;
use App\Entity\VatRate;
use App\Enum\PriceModel;
use PHPUnit\Framework\TestCase;

class CategoryTest extends TestCase
{
    public function testSettersAndGetters(): void
    {
        $user = $this->createMock(User::class);
        $vatRate = $this->createMock(VatRate::class);

        $category = (new Category())
            ->setName('Electronics')
            ->setDefaultMarkup('10.000')
            ->setOwner($user)
            ->setVatRate($vatRate)
            ->setPriceModel(PriceModel::DEFAULT)
            ->setIsActive(true);

        $this->assertEquals('Electronics', $category->getName());
        $this->assertEquals('10.000', $category->getDefaultMarkup());
        $this->assertSame($user, $category->getOwner());
        $this->assertSame($vatRate, $category->getVatRate());
        $this->assertEquals(PriceModel::DEFAULT, $category->getPriceModel());
        $this->assertTrue($category->isActive());
    }

    public function testAddSubcategory(): void
    {
        $category = new Category();
        $subcategory = $this->createMock(Subcategory::class);

        // Test adding a subcategory
        $subcategory->expects($this->once())
            ->method('setCategory')
            ->with($category);

        $category->addSubcategory($subcategory);
        $this->assertCount(1, $category->getSubcategories());
        $this->assertTrue($category->getSubcategories()->contains($subcategory));
    }

    public function testRemoveSubcategory(): void
    {
        $category = new Category();
        $subcategory = $this->createMock(Subcategory::class);

        // Add the subcategory first to set up the state
        $category->addSubcategory($subcategory);

        // Test removing a subcategory
        $subcategory->expects($this->once())
            ->method('getCategory')
            ->willReturn($category);

        $subcategory->expects($this->once())
            ->method('setCategory')
            ->with(null);

        $category->removeSubcategory($subcategory);
        $this->assertCount(0, $category->getSubcategories());
    }

    public function testAddProduct(): void
    {
        $category = new Category();
        $product = $this->createMock(Product::class);

        // Test adding a product
        $product->expects($this->once())
            ->method('setCategory')
            ->with($category);

        $category->addProduct($product);
        $this->assertCount(1, $category->getProducts());
        $this->assertTrue($category->getProducts()->contains($product));
    }

    public function testRemoveProduct(): void
    {
        $category = new Category();
        $product = $this->createMock(Product::class);

        // Add the product first to set up the state
        $category->addProduct($product);

        // Test removing a product
        $product->expects($this->once())
            ->method('getCategory')
            ->willReturn($category);

        $product->expects($this->once())
            ->method('setCategory')
            ->with(null);

        $category->removeProduct($product);
        $this->assertCount(0, $category->getProducts());
    }

    public function testGetActiveProducts(): void
    {
        $category = new Category();

        $activeProduct1 = $this->createMock(Product::class);
        $activeProduct1->method('isActive')->willReturn(true);

        $activeProduct2 = $this->createMock(Product::class);
        $activeProduct2->method('isActive')->willReturn(true);

        $inactiveProduct = $this->createMock(Product::class);
        $inactiveProduct->method('isActive')->willReturn(false);

        $category->addProduct($activeProduct1);
        $category->addProduct($activeProduct2);
        $category->addProduct($inactiveProduct);

        $activeProducts = $category->getActiveProducts();

        $this->assertCount(2, $activeProducts);
        $this->assertTrue($activeProducts->contains($activeProduct1));
        $this->assertTrue($activeProducts->contains($activeProduct2));
        $this->assertFalse($activeProducts->contains($inactiveProduct));
    }
}