<?php

namespace App\Tests\Unit\Service\Sales;

use App\Entity\Product;
use App\Entity\ProductSales;
use App\Entity\PurchaseOrderItem;
use App\Entity\Supplier;
use App\Repository\ProductRepository;
use App\Repository\ProductSalesRepository;
use App\Repository\PurchaseOrderItemRepository;
use App\Repository\SupplierRepository;
use App\Service\Sales\ProductSalesCalculator;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductSalesCalculatorTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private ValidatorInterface $validator;
    private ProductSalesCalculator $productSalesCalculator;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->productSalesCalculator = new ProductSalesCalculator($this->entityManager, $this->validator);
    }

    public function testProcessSuccessfully(): void
    {
        $date = '2023-10-01';
        $salesData = [
            [
                'productId' => 1,
                'supplierId' => 1,
                'salesQty' => 10,
                'salesCost' => 500.00,
                'salesValue' => 1000.00
            ]
        ];

        $product = $this->createMock(Product::class);
        $supplier = $this->createMock(Supplier::class);

        $this->entityManager->method('getRepository')->willReturnMap([
            [PurchaseOrderItem::class, $this->createMock(PurchaseOrderItemRepository::class)],
            [Product::class, $this->createMock(ProductRepository::class)],
            [Supplier::class, $this->createMock(SupplierRepository::class)],
            [ProductSales::class, $this->createMock(ProductSalesRepository::class)]
        ]);

        $this->entityManager->getRepository(PurchaseOrderItem::class)->method('calculateProductSales')->willReturn($salesData);
        $this->entityManager->getRepository(Product::class)->method('find')->willReturn($product);
        $this->entityManager->getRepository(Supplier::class)->method('find')->willReturn($supplier);

        $this->validator->method('validate')->willReturn($this->createMock(ConstraintViolationListInterface::class));

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $this->productSalesCalculator->process($date);
    }

    public function testProcessThrowsExceptionOnValidationFailure(): void
    {
        $date = '2023-10-01';
        $salesData = [
            [
                'productId' => 1,
                'supplierId' => 1,
                'salesQty' => 10,
                'salesCost' => 500.00,
                'salesValue' => 1000.00
            ]
        ];

        $product = $this->createMock(Product::class);
        $supplier = $this->createMock(Supplier::class);

        $this->entityManager->method('getRepository')->willReturnMap([
            [PurchaseOrderItem::class, $this->createMock(PurchaseOrderItemRepository::class)],
            [Product::class, $this->createMock(ProductRepository::class)],
            [Supplier::class, $this->createMock(SupplierRepository::class)],
            [ProductSales::class, $this->createMock(ProductSalesRepository::class)]
        ]);

        $this->entityManager->getRepository(PurchaseOrderItem::class)->method('calculateProductSales')->willReturn($salesData);
        $this->entityManager->getRepository(Product::class)->method('find')->willReturn($product);
        $this->entityManager->getRepository(Supplier::class)->method('find')->willReturn($supplier);

        $violationList = $this->createMock(ConstraintViolationListInterface::class);
        $violationList->method('count')->willReturn(1);
        $violationList->method('__toString')->willReturn('Validation error');

        $this->validator->method('validate')->willReturn($violationList);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Validation error');

        $this->productSalesCalculator->process($date);
    }
}