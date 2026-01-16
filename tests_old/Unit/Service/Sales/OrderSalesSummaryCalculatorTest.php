<?php

namespace App\Tests\Unit\Service\Sales;

use App\Reporting\Application\Handler\CalculateOrderSalesSummaryHandler;
use App\Reporting\Domain\Metric\SalesDuration;
use App\Reporting\Domain\Model\SalesType\OrderSales;
use App\Reporting\Domain\Model\SalesType\OrderSalesSummary;
use App\Reporting\Infrastructure\Persistence\Doctrine\OrderSalesDoctrineRepository;
use App\Reporting\Infrastructure\Persistence\Doctrine\OrderSalesSummaryDoctrineRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class OrderSalesSummaryCalculatorTest extends TestCase
{
    private MockObject $em;

    private MockObject $validator;

    private CalculateOrderSalesSummaryHandler $orderSalesSummaryCalculator;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->orderSalesSummaryCalculator = new CalculateOrderSalesSummaryHandler($this->em, $this->validator);
    }

    public function testProcessSuccessfully(): void
    {
        $salesDurationCount = count(SalesDuration::cases());
        $salesData = [
            [
                'dateString' => '2023-10-01',
                'orderCount' => 10,
                'orderValue' => '1000.00',
                'averageOrderValue' => '100.00',
            ],
        ];

        $this->em->method('getRepository')->willReturnMap([
            [OrderSales::class, $this->createMock(OrderSalesDoctrineRepository::class)],
            [OrderSalesSummary::class, $this->createMock(OrderSalesSummaryDoctrineRepository::class)],
        ]);

        $this->em->getRepository(OrderSales::class)->method('findOrderSalesSummary')->willReturn($salesData);

        $this->validator->method('validate')->willReturn($this->createMock(ConstraintViolationListInterface::class));

        $this->em->expects($this->exactly($salesDurationCount))->method('persist');
        $this->em->expects($this->exactly($salesDurationCount))->method('flush');

        $this->orderSalesSummaryCalculator->process();
    }

    public function testProcessThrowsExceptionOnValidationFailure(): void
    {
        $salesData = [
            [
                'dateString' => '2023-10-01',
                'orderCount' => 10,
                'orderValue' => '1000.00',
                'averageOrderValue' => '100.00',
            ],
        ];

        $this->em->method('getRepository')->willReturnMap([
            [OrderSales::class, $this->createMock(OrderSalesDoctrineRepository::class)],
            [OrderSalesSummary::class, $this->createMock(OrderSalesSummaryDoctrineRepository::class)],
        ]);

        $this->em->getRepository(OrderSales::class)
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
