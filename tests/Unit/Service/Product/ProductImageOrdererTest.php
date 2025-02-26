<?php

namespace App\Tests\Unit\Service\Product;

use App\Entity\Product;
use App\Entity\ProductImage;
use App\Service\Crud\Common\CrudOptions;
use App\Service\Product\ProductImageOrderer;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class ProductImageOrdererTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private ProductImageOrderer $productImageOrderer;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->productImageOrderer = new ProductImageOrderer($this->entityManager);
    }

    public function testHandleWithNonProductEntity(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Entity must be an instance of Product');

        $crudOptions = $this->createMock(CrudOptions::class);
        $crudOptions->method('getEntity')->willReturn(new \stdClass());

        $this->productImageOrderer->handle($crudOptions);
    }

    public function testHandleSuccessfully(): void
    {
        $product = $this->createMock(Product::class);
        $productImage1 = $this->createMock(ProductImage::class);
        $productImage1->method('getId')->willReturn(1);
        $productImage2 = $this->createMock(ProductImage::class);
        $productImage2->method('getId')->willReturn(2);

        $product->method('getProductImages')->willReturn(new ArrayCollection([$productImage1, $productImage2]));

        $crudOptions = $this->createMock(CrudOptions::class);
        $crudOptions->method('getEntity')->willReturn($product);
        $crudOptions->method('getCrudActionContext')->willReturn(['orderedIds' => [2, 1]]);

        $productImage1->expects($this->once())->method('updatePosition')->with(2);
        $productImage2->expects($this->once())->method('updatePosition')->with(1);

        $this->entityManager->expects($this->once())->method('flush');

        $this->productImageOrderer->handle($crudOptions);
    }
}