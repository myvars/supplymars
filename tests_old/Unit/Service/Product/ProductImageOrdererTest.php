<?php

namespace App\Tests\Unit\Service\Product;

use App\Catalog\Domain\Model\Product\Product;
use App\Catalog\Domain\Model\ProductImage\ProductImage;
use App\Service\Crud\Common\CrudContext;
use App\Service\Product\ProductImageOrderer;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductImageOrdererTest extends TestCase
{
    private MockObject $em;

    private ProductImageOrderer $productImageOrderer;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->productImageOrderer = new ProductImageOrderer($this->em);
    }

    public function testHandleWithNonProductEntity(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Entity must be an instance of Product');

        $context = $this->createMock(CrudContext::class);
        $context->method('getEntity')->willReturn(new \stdClass());

        ($this->productImageOrderer)($context);
    }

    public function testHandleSuccessfully(): void
    {
        $product = $this->createMock(Product::class);
        $productImage1 = $this->createMock(ProductImage::class);
        $productImage1->method('getId')->willReturn(1);
        $productImage2 = $this->createMock(ProductImage::class);
        $productImage2->method('getId')->willReturn(2);

        $product->method('getProductImages')->willReturn(new ArrayCollection([$productImage1, $productImage2]));

        $context = $this->createMock(CrudContext::class);
        $context->method('getEntity')->willReturn($product);
        $context->method('getCrudHandlerContext')->willReturn(['orderedIds' => [2, 1]]);

        $productImage1->expects($this->once())->method('changePosition')->with(2);
        $productImage2->expects($this->once())->method('changePosition')->with(1);

        $this->em->expects($this->once())->method('flush');

        ($this->productImageOrderer)($context);
    }
}
