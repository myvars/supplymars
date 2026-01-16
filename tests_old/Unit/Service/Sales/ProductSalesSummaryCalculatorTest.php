<?php

namespace App\Tests\Unit\Service\Sales;

use App\Reporting\Application\Handler\CalculateProductSalesSummaryHandler;
use App\Reporting\Domain\Metric\SalesDuration;
use App\Reporting\Domain\Metric\SalesType;
use App\Reporting\Domain\Model\SalesType\ProductSales;
use App\Reporting\Domain\Model\SalesType\ProductSalesSummary;
use App\Reporting\Infrastructure\Persistence\Doctrine\ProductSalesDoctrineRepository;
use App\Reporting\Infrastructure\Persistence\Doctrine\ProductSalesSummaryDoctrineRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductSalesSummaryCalculatorTest extends TestCase
{
    private MockObject $em;

    private MockObject $validator;

    private CalculateProductSalesSummaryHandler $productSalesSummaryCalculator;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->productSalesSummaryCalculator = new CalculateProductSalesSummaryHandler($this->em, $this->validator);
    }

    public function testProcessSuccessfully(): void
    {
        // Skip day duration for product sales since it is already processed
        // Skip week ago duration for product sales
        $processCount = (count(SalesDuration::cases()) * count(SalesType::cases())) - 2;

        $salesData = [
            [
                'salesId' => 1,
                'dateString' => '2023-10-01',
                'salesQty' => 10,
                'salesCost' => '500.00',
                'salesValue' => '1000.00',
            ],
        ];

        $this->em->method('getRepository')->willReturnMap([
            [ProductSales::class, $this->createMock(ProductSalesDoctrineRepository::class)],
            [ProductSalesSummary::class, $this->createMock(ProductSalesSummaryDoctrineRepository::class)],
        ]);

        $this->em->getRepository(ProductSales::class)->method('findProductSalesSummary')->willReturn($salesData);

        $this->validator->method('validate')->willReturn($this->createMock(ConstraintViolationListInterface::class));

        $this->em->expects($this->exactly($processCount))->method('persist');
        $this->em->expects($this->exactly($processCount))->method('flush');

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
                'salesValue' => '1000.00',
            ],
        ];

        $this->em->method('getRepository')->willReturnMap([
            [ProductSales::class, $this->createMock(ProductSalesDoctrineRepository::class)],
            [ProductSalesSummary::class, $this->createMock(ProductSalesSummaryDoctrineRepository::class)],
        ]);

        $this->em->getRepository(ProductSales::class)->method('findProductSalesSummary')->willReturn($salesData);

        $violationList = $this->createMock(ConstraintViolationListInterface::class);
        $violationList->method('count')->willReturn(1);
        $violationList->method('__toString')->willReturn('Validation error');

        $this->validator->method('validate')->willReturn($violationList);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Validation error');

        $this->productSalesSummaryCalculator->process();
    }
}
