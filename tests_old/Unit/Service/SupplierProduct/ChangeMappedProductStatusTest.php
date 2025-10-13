<?php

namespace App\Tests\Unit\Service\SupplierProduct;

use App\Catalog\Domain\Model\Product\Product;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProduct;
use App\Service\Crud\Common\CrudContext;
use App\Service\Product\ActiveSourceCalculator;
use App\Service\SupplierProduct\ChangeMappedProductStatus;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ChangeMappedProductStatusTest extends TestCase
{
    private MockObject $activeSourceCalculator;

    private ChangeMappedProductStatus $changeMappedProductStatus;

    protected function setUp(): void
    {
        $this->activeSourceCalculator = $this->createMock(ActiveSourceCalculator::class);
        $this->changeMappedProductStatus = new ChangeMappedProductStatus($this->activeSourceCalculator);
    }

    public function testHandleWithNonSupplierProductEntity(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Entity must be an instance of SupplierProduct');

        $context = $this->createMock(CrudContext::class);
        $context->method('getEntity')->willReturn(new \stdClass());

        ($this->changeMappedProductStatus)($context);
    }

    public function testToggleMappedProductStatusSuccessfully(): void
    {
        $product = $this->createMock(Product::class);
        $supplierProduct = $this->createMock(SupplierProduct::class);
        $supplierProduct->method('getProduct')->willReturn($product);

        $context = $this->createMock(CrudContext::class);
        $context->method('getEntity')->willReturn($supplierProduct);

        $this->activeSourceCalculator->expects($this->once())->method('toggleStatus')->with($supplierProduct);
        $this->activeSourceCalculator->expects($this->once())->method('recalculateActiveSource')->with($product);

        ($this->changeMappedProductStatus)($context);
    }
}
