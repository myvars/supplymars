<?php

namespace App\Tests\Unit\Service;


use App\Entity\Product;
use App\Entity\Supplier;
use App\Entity\SupplierProduct;
use App\Repository\ProductRepository;
use App\Service\ActiveSourceCalculator;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class ActiveSourceCalculatorTest extends TestCase
{
    private EntityManagerInterface $entityManagerMock;
    private ProductRepository $productRepositoryMock;

    protected function setUp(): void
    {
        $this->entityManagerMock = $this->createMock(EntityManagerInterface::class);
        $this->productRepositoryMock = $this->createMock(ProductRepository::class);

    }

    public function testRecalculateActiveSourceSetsNewActiveSource(): void
    {
        $activeSourceCalculator = new ActiveSourceCalculator(
            $this->entityManagerMock,
            $this->productRepositoryMock
        );

        $suppler = new Supplier();
        $suppler->setIsActive(true);

        $supplierProduct = new SupplierProduct();
        $supplierProduct
            ->setSupplier($suppler)
            ->setCost('100.00')
            ->setStock(10)
            ->setIsActive(true);

        $product = new Product();
        $product->addSupplierProduct($supplierProduct);

        $this->entityManagerMock->expects($this->once())->method('flush');
        $activeSourceCalculator->recalculateActiveSource($product);
        $this->assertSame($supplierProduct, $product->getActiveProductSource());
    }

    public function testRecalculateActiveSourceWithNoActiveSupplier(): void
    {
        $activeSourceCalculator = new ActiveSourceCalculator(
            $this->entityManagerMock,
            $this->productRepositoryMock
        );

        $suppler = new Supplier();
        $suppler->setIsActive(false);

        $supplierProduct = new SupplierProduct();
        $supplierProduct
            ->setSupplier($suppler)
            ->setCost('100.00')
            ->setStock(10)
            ->setIsActive(true);

        $product = new Product();
        $product->addSupplierProduct($supplierProduct);

        $activeSourceCalculator->recalculateActiveSource($product);
        $this->assertNull($product->getActiveProductSource());
    }

    public function testRecalculateActiveSourceWithNoActiveSupplierProduct(): void
    {
        $activeSourceCalculator = new ActiveSourceCalculator(
            $this->entityManagerMock,
            $this->productRepositoryMock
        );

        $suppler = new Supplier();
        $suppler->setIsActive(true);

        $supplierProduct = new SupplierProduct();
        $supplierProduct
            ->setSupplier($suppler)
            ->setIsActive(false);

        $product = new Product();
        $product->addSupplierProduct($supplierProduct);

        $activeSourceCalculator->recalculateActiveSource($product);
        $this->assertNull($product->getActiveProductSource());
    }

    public function testRecalculateActiveSourceWithMultipleSupplierProducts(): void
    {
        $activeSourceCalculator = new ActiveSourceCalculator(
            $this->entityManagerMock,
            $this->productRepositoryMock
        );

        $suppler = new Supplier();
        $suppler->setIsActive(true);

        $higherCostSupplierProduct = new SupplierProduct();
        $higherCostSupplierProduct
            ->setSupplier($suppler)
            ->setCost('100.00')
            ->setStock(10)
            ->setIsActive(true);
        $lowerCostSupplierProduct = new SupplierProduct();
        $lowerCostSupplierProduct
            ->setSupplier($suppler)
            ->setCost('90.00')
            ->setStock(10)
            ->setIsActive(true);

        $product = new Product();
        $product->addSupplierProduct($higherCostSupplierProduct);
        $product->addSupplierProduct($lowerCostSupplierProduct);

        $activeSourceCalculator->recalculateActiveSource($product);
        $this->assertSame($lowerCostSupplierProduct, $product->getActiveProductSource());
    }

    public function testRecalculateActiveSourceEqualCostsDifferentStocks(): void
    {
        $activeSourceCalculator = new ActiveSourceCalculator(
            $this->entityManagerMock,
            $this->productRepositoryMock
        );

        $suppler = new Supplier();
        $suppler->setIsActive(true);

        $equalCostHighStock = new SupplierProduct();
        $equalCostHighStock
            ->setSupplier($suppler)
            ->setCost('100.00')
            ->setStock(100)
            ->setIsActive(true);
        $equalCostLowStock = new SupplierProduct();
        $equalCostLowStock
            ->setSupplier($suppler)
            ->setCost('100.00')
            ->setStock(50)
            ->setIsActive(true);


        $product = new Product();
        $product->addSupplierProduct($equalCostHighStock);
        $product->addSupplierProduct($equalCostLowStock);

        $activeSourceCalculator->recalculateActiveSource($product);
        $this->assertSame($equalCostHighStock, $product->getActiveProductSource());
    }

    public function testRecalculateActiveSourceWhenCurrentSourceBecomesInactive(): void
    {
        $activeSourceCalculator = new ActiveSourceCalculator(
            $this->entityManagerMock,
            $this->productRepositoryMock
        );

        $suppler = new Supplier();
        $suppler->setIsActive(true);

        $currentActive = new SupplierProduct();
        $currentActive
            ->setSupplier($suppler)
            ->setCost('50.00')
            ->setStock(100)
            ->setIsActive(false);
        $newActive = new SupplierProduct();
        $newActive
            ->setSupplier($suppler)
            ->setCost('100.00')
            ->setStock(100)
            ->setIsActive(true);

        $product = new Product();
        $product->addSupplierProduct($currentActive);
        $product->addSupplierProduct($newActive);
        $product->setActiveProductSource($currentActive);

        $activeSourceCalculator->recalculateActiveSource($product);
        $this->assertSame($newActive, $product->getActiveProductSource());
    }

    public function testRemoveActiveSourceWhenNoActiveSupplier(): void
    {
        $activeSourceCalculator = new ActiveSourceCalculator(
            $this->entityManagerMock,
            $this->productRepositoryMock
        );

        $suppler = new Supplier();
        $suppler->setIsActive(false);

        $inactiveSupplierProduct = new SupplierProduct();
        $inactiveSupplierProduct
            ->setSupplier($suppler)
            ->setCost('50.00')
            ->setStock(100)
            ->setIsActive(true);

        $product = new Product();
        $product->addSupplierProduct($inactiveSupplierProduct);
        $product->setActiveProductSource($inactiveSupplierProduct);

        $activeSourceCalculator->recalculateActiveSource($product);
        $this->assertNull($product->getActiveProductSource());
    }

    public function testRemoveActiveSourceWhenNoActiveSupplierProduct(): void
    {
        $activeSourceCalculator = new ActiveSourceCalculator(
            $this->entityManagerMock,
            $this->productRepositoryMock
        );

        $suppler = new Supplier();
        $suppler->setIsActive(true);

        $inactiveSupplierProduct = new SupplierProduct();
        $inactiveSupplierProduct
            ->setSupplier($suppler)
            ->setCost('50.00')
            ->setStock(100)
            ->setIsActive(false);

        $product = new Product();
        $product->addSupplierProduct($inactiveSupplierProduct);
        $product->setActiveProductSource($inactiveSupplierProduct);

        $activeSourceCalculator->recalculateActiveSource($product);
        $this->assertNull($product->getActiveProductSource());
    }
}