<?php

namespace App\Tests\Unit\Service\Sales;

use App\Entity\CustomerOrder;
use App\Entity\OrderSales;
use App\Repository\CustomerOrderRepository;
use App\Repository\OrderSalesRepository;
use App\Service\Sales\OrderSalesCalculator;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class OrderSalesCalculatorTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private ValidatorInterface $validator;
    private OrderSalesCalculator $orderSalesCalculator;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->orderSalesCalculator = new OrderSalesCalculator($this->entityManager, $this->validator);
    }

    public function testProcessSuccessfully(): void
    {
        $date = '2023-10-01';
        $salesData = [
            [
                'orderCount' => 10,
                'orderValue' => '1000.00',
                'averageOrderValue' => '100.00'
            ]
        ];

        $this->entityManager->method('getRepository')->willReturnMap([
            [CustomerOrder::class, $this->createMock(CustomerOrderRepository::class)],
            [OrderSales::class, $this->createMock(OrderSalesRepository::class)]
        ]);

        $this->entityManager->getRepository(CustomerOrder::class)
            ->method('findOrderSalesByDate')
            ->willReturn($salesData);

        $this->validator->method('validate')->willReturn($this->createMock(ConstraintViolationListInterface::class));

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $this->orderSalesCalculator->process($date);
    }

    public function testProcessThrowsExceptionOnValidationFailure(): void
    {
        $date = '2023-10-01';
        $salesData = [
            [
                'orderCount' => 10,
                'orderValue' => '1000.00',
                'averageOrderValue' => '100.00'
            ]
        ];

        $this->entityManager->method('getRepository')->willReturnMap([
            [CustomerOrder::class, $this->createMock(CustomerOrderRepository::class)],
            [OrderSales::class, $this->createMock(OrderSalesRepository::class)]
        ]);

        $this->entityManager->getRepository(CustomerOrder::class)
            ->method('findOrderSalesByDate')
            ->willReturn($salesData);

        $violationList = $this->createMock(ConstraintViolationListInterface::class);
        $violationList->method('count')->willReturn(1);
        $violationList->method('__toString')->willReturn('Validation error');

        $this->validator->method('validate')->willReturn($violationList);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Validation error');

        $this->orderSalesCalculator->process($date);
    }
}