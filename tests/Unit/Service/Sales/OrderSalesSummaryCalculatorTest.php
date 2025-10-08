<?php

namespace App\Tests\Unit\Service\Sales;

use PHPUnit\Framework\MockObject\MockObject;
use App\Entity\OrderSales;
use App\Entity\OrderSalesSummary;
use App\Enum\SalesDuration;
use App\Repository\OrderSalesRepository;
use App\Repository\OrderSalesSummaryRepository;
use App\Service\Sales\OrderSalesSummaryCalculator;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class OrderSalesSummaryCalculatorTest extends TestCase
{
    private MockObject $entityManager;

    private MockObject $validator;

    private OrderSalesSummaryCalculator $orderSalesSummaryCalculator;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->orderSalesSummaryCalculator = new OrderSalesSummaryCalculator($this->entityManager, $this->validator);
    }

    public function testProcessSuccessfully(): void
    {
        $salesDurationCount = count(SalesDuration::cases());
        $salesData = [
            [
                'dateString' => '2023-10-01',
                'orderCount' => 10,
                'orderValue' => '1000.00',
                'averageOrderValue' => '100.00'
            ]
        ];

        $this->entityManager->method('getRepository')->willReturnMap([
            [OrderSales::class, $this->createMock(OrderSalesRepository::class)],
            [OrderSalesSummary::class, $this->createMock(OrderSalesSummaryRepository::class)]
        ]);

        $this->entityManager->getRepository(OrderSales::class)->method('findOrderSalesSummary')->willReturn($salesData);

        $this->validator->method('validate')->willReturn($this->createMock(ConstraintViolationListInterface::class));

        $this->entityManager->expects($this->exactly($salesDurationCount))->method('persist');
        $this->entityManager->expects($this->exactly($salesDurationCount))->method('flush');

        $this->orderSalesSummaryCalculator->process();
    }

    public function testProcessThrowsExceptionOnValidationFailure(): void
    {
        $salesData = [
            [
                'dateString' => '2023-10-01',
                'orderCount' => 10,
                'orderValue' => '1000.00',
                'averageOrderValue' => '100.00'
            ]
        ];

        $this->entityManager->method('getRepository')->willReturnMap([
            [OrderSales::class, $this->createMock(OrderSalesRepository::class)],
            [OrderSalesSummary::class, $this->createMock(OrderSalesSummaryRepository::class)]
        ]);

        $this->entityManager->getRepository(OrderSales::class)
            ->method('findOrderSalesSummary')
            ->willReturn($salesData);

        $violationList = $this->createMock(ConstraintViolationListInterface::class);
        $violationList->method('count')->willReturn(1);
        $violationList->method('__toString')->willReturn('Validation error');

        $this->validator->method('validate')->willReturn($violationList);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Validation error');

        $this->orderSalesSummaryCalculator->process();
    }
}