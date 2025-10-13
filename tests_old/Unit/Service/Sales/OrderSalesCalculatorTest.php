<?php

namespace App\Tests\Unit\Service\Sales;

use App\Order\Domain\Model\Order\CustomerOrder;
use App\Order\Infrastructure\Persistence\Doctrine\CustomerOrderDoctrineRepository;
use App\Reporting\Application\Handler\CalculateOrderSalesHandler;
use App\Reporting\Domain\Model\SalesType\OrderSales;
use App\Reporting\Infrastructure\Persistence\Doctrine\OrderSalesDoctrineRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class OrderSalesCalculatorTest extends TestCase
{
    private MockObject $em;

    private MockObject $validator;

    private CalculateOrderSalesHandler $orderSalesCalculator;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->orderSalesCalculator = new CalculateOrderSalesHandler($this->em, $this->validator);
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

        $this->em->method('getRepository')->willReturnMap([
            [CustomerOrder::class, $this->createMock(CustomerOrderDoctrineRepository::class)],
            [OrderSales::class, $this->createMock(OrderSalesDoctrineRepository::class)]
        ]);

        $this->em->getRepository(CustomerOrder::class)
            ->method('findOrderSalesByDate')
            ->willReturn($salesData);

        $this->validator->method('validate')->willReturn($this->createMock(ConstraintViolationListInterface::class));

        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

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

        $this->em->method('getRepository')->willReturnMap([
            [CustomerOrder::class, $this->createMock(CustomerOrderDoctrineRepository::class)],
            [OrderSales::class, $this->createMock(OrderSalesDoctrineRepository::class)]
        ]);

        $this->em->getRepository(CustomerOrder::class)
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
