<?php

namespace App\Tests\Unit\Service\Product;

use App\Entity\Product;
use App\Entity\SupplierProduct;
use App\Repository\ProductRepository;
use App\Service\Product\ActiveSourceCalculator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class ActiveSourceCalculatorTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private ActiveSourceCalculator $activeSourceCalculator;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $productRepository = $this->createMock(ProductRepository::class);
        $this->activeSourceCalculator = new ActiveSourceCalculator($this->entityManager, $productRepository);
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
        $this->entityManager->expects($this->once())->method('flush');

        $this->activeSourceCalculator->recalculateActiveSource($product);
    }

    public function testRecalculateActiveSourceWithoutSupplierProduct(): void
    {
        $product = $this->createMock(Product::class);
        $product->method('getSupplierProducts')->willReturn(new ArrayCollection([]));

        $product->expects($this->once())->method('setActiveProductSource')->with(null);
        $product->expects($this->once())->method('setStock')->with(0);
        $this->entityManager->expects($this->once())->method('flush');

        $this->activeSourceCalculator->recalculateActiveSource($product);
    }

    public function testToggleStatus(): void
    {
        $supplierProduct = $this->createMock(SupplierProduct::class);
        $supplierProduct->method('isActive')->willReturn(true);

        $supplierProduct->expects($this->once())->method('setIsActive')->with(false);
        $this->entityManager->expects($this->once())->method('persist')->with($supplierProduct);
        $this->entityManager->expects($this->once())->method('flush');

        $this->activeSourceCalculator->toggleStatus($supplierProduct);
    }
}