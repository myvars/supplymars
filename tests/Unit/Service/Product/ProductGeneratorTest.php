<?php

namespace App\Tests\Unit\Service\Product;

use App\Entity\Category;
use App\Entity\Manufacturer;
use App\Entity\Product;
use App\Entity\Subcategory;
use App\Entity\SupplierProduct;
use App\Service\Crud\Common\CrudOptions;
use App\Service\Product\CategoryMapper;
use App\Service\Product\ManufacturerMapper;
use App\Service\Product\ProductGenerator;
use App\Service\Product\ProductMapper;
use App\Service\Product\ProductPriceCalculator;
use App\Service\Product\SubcategoryMapper;
use PHPUnit\Framework\TestCase;

class ProductGeneratorTest extends TestCase
{
    private CategoryMapper $categoryMapper;
    private SubcategoryMapper $subcategoryMapper;
    private ManufacturerMapper $manufacturerMapper;
    private ProductMapper $productMapper;
    private ProductPriceCalculator $productPriceCalculator;
    private ProductGenerator $productGenerator;

    protected function setUp(): void
    {
        $this->categoryMapper = $this->createMock(CategoryMapper::class);
        $this->subcategoryMapper = $this->createMock(SubcategoryMapper::class);
        $this->manufacturerMapper = $this->createMock(ManufacturerMapper::class);
        $this->productMapper = $this->createMock(ProductMapper::class);
        $this->productPriceCalculator = $this->createMock(ProductPriceCalculator::class);
        $this->productGenerator = new ProductGenerator(
            $this->categoryMapper,
            $this->subcategoryMapper,
            $this->manufacturerMapper,
            $this->productMapper,
            $this->productPriceCalculator
        );
    }

    public function testHandleWithNonSupplierProductEntity(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Entity must be an instance of SupplierProduct');

        $crudOptions = $this->createMock(CrudOptions::class);
        $crudOptions->method('getEntity')->willReturn(new \stdClass());

        $this->productGenerator->handle($crudOptions);
    }

    public function testCreateFromSupplierProductWithExistingProduct(): void
    {
        $supplierProduct = $this->createMock(SupplierProduct::class);
        $supplierProduct->method('getProduct')->willReturn($this->createMock(Product::class));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Product already exists');

        $this->productGenerator->createFromSupplierProduct($supplierProduct);
    }

    public function testCreateFromSupplierProductSuccessfully(): void
    {
        $supplierProduct = $this->createMock(SupplierProduct::class);
        $supplierProduct->method('getProduct')->willReturn(null);

        $manufacturer = $this->createMock(Manufacturer::class);
        $category = $this->createMock(Category::class);
        $subcategory = $this->createMock(Subcategory::class);
        $product = $this->createMock(Product::class);

        $this->manufacturerMapper->method('createManufacturerFromSupplierProduct')->willReturn($manufacturer);
        $this->categoryMapper->method('createCategoryFromSupplierProduct')->willReturn($category);
        $this->subcategoryMapper->method('createSubcategoryFromSupplierProduct')->willReturn($subcategory);
        $this->productMapper->method('createProductFromSupplierProduct')->willReturn($product);

        $this->productPriceCalculator->expects($this->once())->method('recalculatePrice')->with($product, true);

        $createdProduct = $this->productGenerator->createFromSupplierProduct($supplierProduct);

        $this->assertInstanceOf(Product::class, $createdProduct);
    }
}