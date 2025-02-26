<?php

namespace App\Tests\Unit\Service\Sales;

use App\Entity\ProductSales;
use App\Entity\ProductSalesSummary;
use App\Enum\SalesDuration;
use App\Enum\SalesType;
use App\Repository\ProductSalesRepository;
use App\Repository\ProductSalesSummaryRepository;
use App\Service\Sales\ProductSalesSummaryCalculator;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductSalesSummaryCalculatorTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private ValidatorInterface $validator;
    private ProductSalesSummaryCalculator $productSalesSummaryCalculator;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->productSalesSummaryCalculator = new ProductSalesSummaryCalculator($this->entityManager, $this->validator);
    }

    public function testProcessSuccessfully(): void
    {
        // Skip day duration for product sales since it is already processed
        // Skip week ago duration for product sales
        $processCount = (count(SalesDuration::cases()) * count(SalesType::cases())) -2;

        $salesData = [
            [
                'salesId' => 1,
                'dateString' => '2023-10-01',
                'salesQty' => 10,
                'salesCost' => '500.00',
                'salesValue' => '1000.00'
            ]
        ];

        $this->entityManager->method('getRepository')->willReturnMap([
            [ProductSales::class, $this->createMock(ProductSalesRepository::class)],
            [ProductSalesSummary::class, $this->createMock(ProductSalesSummaryRepository::class)]
        ]);

        $this->entityManager->getRepository(ProductSales::class)->method('findProductSalesSummary')->willReturn($salesData);

        $this->validator->method('validate')->willReturn($this->createMock(ConstraintViolationListInterface::class));

        $this->entityManager->expects($this->exactly($processCount))->method('persist');
        $this->entityManager->expects($this->exactly($processCount))->method('flush');

        $this->productSalesSummaryCalculator->process();
    }

    public function testProcessThrowsExceptionOnValidationFailure(): void
    {
        $salesData = [
            [
                'salesId' => 1,
                'dateString' => '2023-10-01',
                'salesQty' => 10,
                'salesCost' => '500.00',
                'salesValue' => '1000.00'
            ]
        ];

        $this->entityManager->method('getRepository')->willReturnMap([
            [ProductSales::class, $this->createMock(ProductSalesRepository::class)],
            [ProductSalesSummary::class, $this->createMock(ProductSalesSummaryRepository::class)]
        ]);

        $this->entityManager->getRepository(ProductSales::class)->method('findProductSalesSummary')->willReturn($salesData);

        $violationList = $this->createMock(ConstraintViolationListInterface::class);
        $violationList->method('count')->willReturn(1);
        $violationList->method('__toString')->willReturn('Validation error');

        $this->validator->method('validate')->willReturn($violationList);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Validation error');

        $this->productSalesSummaryCalculator->process();
    }
}