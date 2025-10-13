<?php

namespace App\Tests\Unit\Service\Sales;

use App\Catalog\Domain\Model\Product\Product;
use App\Catalog\Infrastructure\Persistence\Doctrine\ProductDoctrineRepository;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItem;
use App\Purchasing\Domain\Model\Supplier\Supplier;
use App\Purchasing\Infrastructure\Persistence\Doctrine\PurchaseOrderItemDoctrineRepository;
use App\Purchasing\Infrastructure\Persistence\Doctrine\SupplierDoctrineRepository;
use App\Reporting\Application\Handler\CalculateProductSalesHandler;
use App\Reporting\Domain\Model\SalesType\ProductSales;
use App\Reporting\Infrastructure\Persistence\Doctrine\ProductSalesDoctrineRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductSalesCalculatorTest extends TestCase
{
    private MockObject $em;

    private MockObject $validator;

    private CalculateProductSalesHandler $productSalesCalculator;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->productSalesCalculator = new CalculateProductSalesHandler($this->em, $this->validator);
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

        $this->em->method('getRepository')->willReturnMap([
            [PurchaseOrderItem::class, $this->createMock(PurchaseOrderItemDoctrineRepository::class)],
            [Product::class, $this->createMock(ProductDoctrineRepository::class)],
            [Supplier::class, $this->createMock(SupplierDoctrineRepository::class)],
            [ProductSales::class, $this->createMock(ProductSalesDoctrineRepository::class)]
        ]);

        $this->em->getRepository(PurchaseOrderItem::class)->method('calculateProductSales')->willReturn($salesData);
        $this->em->getRepository(Product::class)->method('find')->willReturn($product);
        $this->em->getRepository(Supplier::class)->method('find')->willReturn($supplier);

        $this->validator->method('validate')->willReturn($this->createMock(ConstraintViolationListInterface::class));

        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

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

        $this->em->method('getRepository')->willReturnMap([
            [PurchaseOrderItem::class, $this->createMock(PurchaseOrderItemDoctrineRepository::class)],
            [Product::class, $this->createMock(ProductDoctrineRepository::class)],
            [Supplier::class, $this->createMock(SupplierDoctrineRepository::class)],
            [ProductSales::class, $this->createMock(ProductSalesDoctrineRepository::class)]
        ]);

        $this->em->getRepository(PurchaseOrderItem::class)->method('calculateProductSales')->willReturn($salesData);
        $this->em->getRepository(Product::class)->method('find')->willReturn($product);
        $this->em->getRepository(Supplier::class)->method('find')->willReturn($supplier);

        $violationList = $this->createMock(ConstraintViolationListInterface::class);
        $violationList->method('count')->willReturn(1);
        $violationList->method('__toString')->willReturn('Validation error');

        $this->validator->method('validate')->willReturn($violationList);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Validation error');

        $this->productSalesCalculator->process($date);
    }
}
