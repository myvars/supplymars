<?php

namespace App\Tests\Unit\Service\Product;

use App\Catalog\Domain\Model\Product\Product;
use App\Catalog\Infrastructure\Persistence\Doctrine\ProductDoctrineRepository;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProduct;
use App\Service\Product\ActiveSourceCalculator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ActiveSourceCalculatorTest extends TestCase
{
    private MockObject $em;

    private ActiveSourceCalculator $activeSourceCalculator;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $productRepository = $this->createMock(ProductDoctrineRepository::class);
        $this->activeSourceCalculator = new ActiveSourceCalculator($this->em, $productRepository);
    }

    public function testRecalculateActiveSource(): void
    {
        $product = $this->createMock(Product::class);
        $supplierProduct = $this->createMock(SupplierProduct::class);

        $product->method('getSupplierProducts')->willReturn(new ArrayCollection([$supplierProduct]));
        $supplierProduct->method('hasActiveSupplier')->willReturn(true);
        $supplierProduct->method('isActive')->willReturn(true);
        $supplierProduct->method('hasStock')->willReturn(true);
        $supplierProduct->method('getStock')->willReturn(10);
        $supplierProduct->method('getCost')->willReturn('100.00');
        $supplierProduct->method('getLeadTimeDays')->willReturn(7);

        $product->expects($this->once())->method('setActiveProductSource')->with($supplierProduct);
        $this->em->expects($this->once())->method('flush');

        $this->activeSourceCalculator->recalculateActiveSource($product);
    }

    public function testRecalculateActiveSourceWithoutSupplierProduct(): void
    {
        $product = $this->createMock(Product::class);
        $product->method('getSupplierProducts')->willReturn(new ArrayCollection([]));

        $product->expects($this->once())->method('setActiveProductSource')->with(null);
        $product->expects($this->once())->method('setStock')->with(0);
        $this->em->expects($this->once())->method('flush');

        $this->activeSourceCalculator->recalculateActiveSource($product);
    }

    public function testToggleStatus(): void
    {
        $supplierProduct = $this->createMock(SupplierProduct::class);
        $supplierProduct->method('isActive')->willReturn(true);

        $supplierProduct->expects($this->once())->method('setIsActive')->with(false);
        $this->em->expects($this->once())->method('persist')->with($supplierProduct);
        $this->em->expects($this->once())->method('flush');

        $this->activeSourceCalculator->toggleStatus($supplierProduct);
    }
}
