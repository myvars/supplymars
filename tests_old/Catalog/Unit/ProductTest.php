<?php

namespace App\Tests\Catalog\Unit;

use App\Catalog\Domain\Model\Category\Category;
use App\Catalog\Domain\Model\Manufacturer\Manufacturer;
use App\Catalog\Domain\Model\Product\Product;
use App\Catalog\Domain\Model\ProductImage\ProductImage;
use App\Catalog\Domain\Model\Subcategory\Subcategory;
use App\Customer\Domain\Model\User\User;
use App\Purchasing\Domain\Model\Supplier\Supplier;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProduct;
use App\Shared\Domain\ValueObject\PriceModel;
use PHPUnit\Framework\TestCase;

class ProductTest extends TestCase
{
    public function testSettersAndGetters(): void
    {
        $category = $this->createMock(Category::class);
        $subcategory = $this->createMock(Subcategory::class);
        $manufacturer = $this->createMock(Manufacturer::class);
        $owner = $this->createMock(User::class);
        $priceModel = PriceModel::PRETTY_99;

        $product = new Product()
            ->setName('Test Product')
            ->setMfrPartNumber('Test MfrPartNumber')
            ->setStock(100)
            ->setLeadTimeDays(7)
            ->setWeight(500)
            ->setDefaultMarkup('0.21')
            ->setMarkup('0.21')
            ->setCost('100.00')
            ->setSellPrice('150.00')
            ->setSellPriceIncVat('180.00')
            ->assignCategory($category)
            ->setSubcategory($subcategory)
            ->setManufacturer($manufacturer)
            ->setOwner($owner)
            ->setPriceModel($priceModel);

        $this->assertEquals('Test Product', $product->getName());
        $this->assertEquals('Test MfrPartNumber', $product->getMfrPartNumber());
        $this->assertEquals(100, $product->getStock());
        $this->assertEquals(7, $product->getLeadTimeDays());
        $this->assertEquals(500, $product->getWeight());
        $this->assertEquals('0.21', $product->getDefaultMarkup());
        $this->assertEquals('0.21', $product->getMarkup());
        $this->assertEquals('100.00', $product->getCost());
        $this->assertEquals('150.00', $product->getSellPrice());
        $this->assertEquals('180.00', $product->getSellPriceIncVat());
        $this->assertSame($category, $product->getCategory());
        $this->assertSame($subcategory, $product->getSubcategory());
        $this->assertSame($manufacturer, $product->getManufacturer());
        $this->assertSame($owner, $product->getOwner());
        $this->assertSame($priceModel, $product->getPriceModel());
    }

    public function testAddSupplierProduct(): void
    {
        $product = new Product();
        $supplierProduct = $this->createMock(SupplierProduct::class);

        $supplierProduct->expects($this->once())
            ->method('setProduct')
            ->with($product);

        $product->addSupplierProduct($supplierProduct);
        $this->assertCount(1, $product->getSupplierProducts());
        $this->assertTrue($product->getSupplierProducts()->contains($supplierProduct));
    }

    public function testRemoveSupplierProduct(): void
    {
        $product = new Product();
        $supplierProduct = $this->createMock(SupplierProduct::class);

        $product->addSupplierProduct($supplierProduct);

        $supplierProduct->expects($this->once())
            ->method('getProduct')
            ->willReturn($product);

        $product->removeSupplierProduct($supplierProduct);
        $this->assertCount(0, $product->getSupplierProducts());
    }

    public function testAddProductImage(): void
    {
        $product = new Product();
        $productImage = $this->createMock(ProductImage::class);

        $productImage->expects($this->once())
            ->method('assignProduct')
            ->with($product);

        $product->addProductImage($productImage);
        $this->assertCount(1, $product->getProductImages());
        $this->assertTrue($product->getProductImages()->contains($productImage));
    }

    public function testRemoveProductImage(): void
    {
        $product = new Product();
        $productImage = $this->createMock(ProductImage::class);

        $product->addProductImage($productImage);

        $productImage->expects($this->once())
            ->method('getProduct')
            ->willReturn($product);

        $product->removeProductImage($productImage);
        $this->assertCount(0, $product->getProductImages());
    }

    public function testHasDefaultMarkup(): void
    {
        $product = new Product();
        $product->applyDefaultMarkup('0.21');
        $this->assertTrue($product->hasDefaultMarkup());
    }

    public function testHasOwner(): void
    {
        $product = new Product();
        $owner = $this->createMock(User::class);
        $product->setOwner($owner);
        $this->assertTrue($product->hasOwner());
    }

    public function testHasPriceModel(): void
    {
        $product = new Product();
        $product->setPriceModel(PriceModel::PRETTY_99);
        $this->assertTrue($product->hasPriceModel());
    }

    public function testHasActiveProductSource(): void
    {
        $product = new Product();
        $supplierProduct = $this->createMock(SupplierProduct::class);
        $product->setActiveProductSource($supplierProduct);
        $this->assertTrue($product->hasActiveProductSource());
    }

    public function testHasProductImage(): void
    {
        $product = new Product();
        $productImage = $this->createMock(ProductImage::class);
        $product->addProductImage($productImage);
        $this->assertTrue($product->hasProductImage());
    }

    public function testGetFirstImage(): void
    {
        $product = new Product();
        $productImage = $this->createMock(ProductImage::class);
        $product->addProductImage($productImage);
        $this->assertSame($productImage, $product->getFirstImage());
    }

    public function testGetActiveMarkup(): void
    {
        $product = new Product();
        $product->applyDefaultMarkup('0.21');
        $this->assertEquals('0.21', $product->getActiveMarkup());
    }

    public function testGetActiveMarkupTarget(): void
    {
        $product = new Product();
        $product->applyDefaultMarkup('0.21');
        $this->assertEquals('PRODUCT', $product->getActiveMarkupTarget());
    }

    public function testGetActivePriceModel(): void
    {
        $product = new Product();
        $product->setPriceModel(PriceModel::PRETTY_99);
        $this->assertSame(PriceModel::PRETTY_99, $product->getActivePriceModel());
    }

    public function testGetActivePriceModelTarget(): void
    {
        $product = new Product();
        $product->setPriceModel(PriceModel::PRETTY_99);
        $this->assertEquals('PRODUCT', $product->getActivePriceModelTarget());
    }

    public function testIsValidProduct(): void
    {
        $product = new Product();
        $category = $this->createMock(Category::class);
        $subcategory = $this->createMock(Subcategory::class);
        $supplierProduct = $this->createMock(SupplierProduct::class);
        $supplier = $this->createMock(Supplier::class);

        $category->method('isActive')->willReturn(true);
        $subcategory->method('isActive')->willReturn(true);
        $supplierProduct->method('isActive')->willReturn(true);
        $supplierProduct->method('getSupplier')->willReturn($supplier);

        $product->setIsActive(true)
            ->assignCategory($category)
            ->setSubcategory($subcategory)
            ->setActiveProductSource($supplierProduct);

        $this->assertTrue($product->isValidProduct());
    }
}
