<?php

namespace App\Tests\Unit\Service\SupplierProduct;

use PHPUnit\Framework\MockObject\MockObject;
use App\Entity\Product;
use App\Entity\SupplierProduct;
use App\Service\Crud\Common\CrudOptions;
use App\Service\Product\ActiveSourceCalculator;
use App\Service\SupplierProduct\RemoveMappedProduct;
use PHPUnit\Framework\TestCase;

class RemoveMappedProductTest extends TestCase
{
    private MockObject $activeSourceCalculator;

    private RemoveMappedProduct $removeMappedProduct;

    protected function setUp(): void
    {
        $this->activeSourceCalculator = $this->createMock(ActiveSourceCalculator::class);
        $this->removeMappedProduct = new RemoveMappedProduct($this->activeSourceCalculator);
    }

    public function testHandleWithNonSupplierProductEntity(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Entity must be an instance of SupplierProduct');

        $crudOptions = $this->createMock(CrudOptions::class);
        $crudOptions->method('getEntity')->willReturn(new \stdClass());

        $this->removeMappedProduct->handle($crudOptions);
    }

    public function testHandleWithInvalidProduct(): void
    {
        $supplierProduct = $this->createMock(SupplierProduct::class);
        $supplierProduct->method('getProduct')->willReturn(null);

        $crudOptions = $this->createMock(CrudOptions::class);
        $crudOptions->method('getEntity')->willReturn($supplierProduct);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Supplier product must be mapped to a product');

        $this->removeMappedProduct->handle($crudOptions);
    }

    public function testRemoveMappedProductSuccessfully(): void
    {
        $product = $this->createMock(Product::class);
        $supplierProduct = $this->createMock(SupplierProduct::class);
        $supplierProduct->method('getProduct')->willReturn($product);

        $crudOptions = $this->createMock(CrudOptions::class);
        $crudOptions->method('getEntity')->willReturn($supplierProduct);

        $this->activeSourceCalculator->expects($this->once())->method('removeMappedProduct')->with($supplierProduct);
        $this->activeSourceCalculator->expects($this->once())->method('recalculateActiveSource')->with($product);

        $this->removeMappedProduct->handle($crudOptions);
    }
}
