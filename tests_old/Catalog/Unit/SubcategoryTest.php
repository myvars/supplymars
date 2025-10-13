<?php

namespace App\Tests\Catalog\Unit;

use App\Catalog\Domain\Model\Category\Category;
use App\Catalog\Domain\Model\Product\Product;
use App\Catalog\Domain\Model\Subcategory\Subcategory;
use App\Customer\Domain\Model\User\User;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierSubcategory;
use App\Shared\Domain\ValueObject\PriceModel;
use PHPUnit\Framework\TestCase;

class SubcategoryTest extends TestCase
{
    public function testSettersAndGetters(): void
    {
        $user = $this->createMock(User::class);
        $category = $this->createMock(Category::class);

        $subcategory = new SubCategory()
            ->setName('Electronics')
            ->setDefaultMarkup('10.000')
            ->setOwner($user)
            ->setCategory($category)
            ->setPriceModel(PriceModel::DEFAULT)
            ->setIsActive(true);

        $this->assertEquals('Electronics', $subcategory->getName());
        $this->assertEquals('10.000', $subcategory->getDefaultMarkup());
        $this->assertSame($user, $subcategory->getOwner());
        $this->assertSame($category, $subcategory->getCategory());
        $this->assertEquals(PriceModel::DEFAULT, $subcategory->getPriceModel());
        $this->assertTrue($subcategory->isActive());
    }

    public function testAddProduct(): void
    {
        $subcategory = new Subcategory();
        $product = $this->createMock(Product::class);

        // Test adding a product
        $product->expects($this->once())
            ->method('assignSubcategory')
            ->with($subcategory);

        $subcategory->addProduct($product);
        $this->assertCount(1, $subcategory->getProducts());
        $this->assertTrue($subcategory->getProducts()->contains($product));
    }

    public function testRemoveProduct(): void
    {
        $subcategory = new Subcategory();
        $product = $this->createMock(Product::class);

        // Add the product first to set up the state
        $subcategory->addProduct($product);

        // Test removing a product
        $product->expects($this->once())
            ->method('getSubcategory')
            ->willReturn($subcategory);

        $product->expects($this->once())
            ->method('assignSubcategory')
            ->with(null);

        $subcategory->removeProduct($product);
        $this->assertCount(0, $subcategory->getProducts());
    }

    public function testGetActiveProducts(): void
    {
        $subcategory = new Subcategory();

        $activeProduct1 = $this->createMock(Product::class);
        $activeProduct1->method('isActive')->willReturn(true);

        $activeProduct2 = $this->createMock(Product::class);
        $activeProduct2->method('isActive')->willReturn(true);

        $inactiveProduct = $this->createMock(Product::class);
        $inactiveProduct->method('isActive')->willReturn(false);

        $subcategory->addProduct($activeProduct1);
        $subcategory->addProduct($activeProduct2);
        $subcategory->addProduct($inactiveProduct);

        $activeProducts = $subcategory->getActiveProducts();

        $this->assertCount(2, $activeProducts);
        $this->assertTrue($activeProducts->contains($activeProduct1));
        $this->assertTrue($activeProducts->contains($activeProduct2));
        $this->assertFalse($activeProducts->contains($inactiveProduct));
    }

    public function testAddSupplierSubcategory(): void
    {
        $subcategory = new Subcategory();
        $supplierSubcategory = $this->createMock(SupplierSubcategory::class);

        // Test adding a supplier subcategory
        $supplierSubcategory->expects($this->once())
            ->method('assignMappedSubcategory')
            ->with($subcategory);

        $subcategory->addSupplierSubcategory($supplierSubcategory);
        $this->assertCount(1, $subcategory->getSupplierSubcategories());
        $this->assertTrue($subcategory->getSupplierSubcategories()->contains($supplierSubcategory));
    }

    public function testRemoveSupplierSubcategory(): void
    {
        $subcategory = new Subcategory();
        $supplierSubcategory = $this->createMock(SupplierSubcategory::class);

        // Add the supplier subcategory first to set up the state
        $subcategory->addSupplierSubcategory($supplierSubcategory);

        // Test removing a supplier subcategory
        $supplierSubcategory->expects($this->once())
            ->method('getMappedSubcategory')
            ->willReturn($subcategory);

        $supplierSubcategory->expects($this->once())
            ->method('assignMappedSubcategory')
            ->with(null);

        $subcategory->removeSupplierSubcategory($supplierSubcategory);
        $this->assertCount(0, $subcategory->getSupplierSubcategories());
    }

    public function testHasDefaultMarkup(): void
    {
        $subcategory = new Subcategory();
        $subcategory->setDefaultMarkup('0.000');
        $this->assertFalse($subcategory->hasDefaultMarkup());

        $subcategory->setDefaultMarkup('1.000');
        $this->assertTrue($subcategory->hasDefaultMarkup());
    }

    public function testHasOwner(): void
    {
        $subcategory = new Subcategory();
        $this->assertFalse($subcategory->hasOwner());

        $owner = $this->createMock(User::class);
        $subcategory->assignOwner($owner);
        $this->assertTrue($subcategory->hasOwner());
    }

    public function testHasPriceModel(): void
    {
        $subcategory = new Subcategory();
        $subcategory->setPriceModel(PriceModel::NONE);
        $this->assertFalse($subcategory->hasPriceModel());

        $subcategory->setPriceModel(PriceModel::DEFAULT);
        $this->assertTrue($subcategory->hasPriceModel());
    }
}
