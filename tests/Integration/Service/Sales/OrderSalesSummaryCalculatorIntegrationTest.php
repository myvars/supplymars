<?php

namespace App\Tests\Integration\Service\Sales;

use App\Entity\OrderSalesSummary;
use App\Enum\SalesDuration;
use App\Factory\OrderSalesFactory;
use App\Factory\OrderSalesSummaryFactory;
use App\Service\Sales\OrderSalesSummaryCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Test\Factories;

class OrderSalesSummaryCalculatorIntegrationTest extends KernelTestCase
{
    use Factories;

    private OrderSalesSummaryCalculator $orderSalesSummaryCalculator;

    protected function setUp(): void
    {
        self::bootKernel();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $validator = static::getContainer()->get(ValidatorInterface::class);
        $this->orderSalesSummaryCalculator = new OrderSalesSummaryCalculator($entityManager, $validator);
    }

    public function testProcessedSuccessfully(): void
    {
        $date = (new \DateTime())->format('Y-m-d');
        OrderSalesFactory::createOne([
            'dateString' => $date,
            'orderCount' => 10,
            'orderValue' => '1000.00',
            'averageOrderValue' => '100.00'
        ]);

        $this->orderSalesSummaryCalculator->process();

        $orderSalesSummary = OrderSalesSummaryFactory::repository()->findOneBy([
            'duration' => SalesDuration::LAST_7->value,
            'dateString' => SalesDuration::LAST_7->getStartDate()
        ]);

        $this->assertInstanceOf(OrderSalesSummary::class, $orderSalesSummary);

        $this->assertSame(10, $orderSalesSummary->getOrderCount());
        $this->assertSame('1000.00', $orderSalesSummary->getOrderValue());
        $this->assertSame('100.00', $orderSalesSummary->getAverageOrderValue());
    }
}