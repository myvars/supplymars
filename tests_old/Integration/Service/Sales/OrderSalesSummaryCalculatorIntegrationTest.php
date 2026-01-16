<?php

namespace App\Tests\Integration\Service\Sales;

use App\Reporting\Application\Handler\CalculateOrderSalesSummaryHandler;
use App\Reporting\Domain\Metric\SalesDuration;
use App\Reporting\Domain\Model\SalesType\OrderSalesSummary;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use tests\Shared\Factory\OrderSalesFactory;
use tests\Shared\Factory\OrderSalesSummaryFactory;
use Zenstruck\Foundry\Test\Factories;

class OrderSalesSummaryCalculatorIntegrationTest extends KernelTestCase
{
    use Factories;

    private CalculateOrderSalesSummaryHandler $orderSalesSummaryCalculator;

    protected function setUp(): void
    {
        self::bootKernel();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $validator = static::getContainer()->get(ValidatorInterface::class);
        $this->orderSalesSummaryCalculator = new CalculateOrderSalesSummaryHandler($em, $validator);
    }

    public function testProcessedSuccessfully(): void
    {
        $date = new \DateTime()->format('Y-m-d');
        OrderSalesFactory::createOne([
            'dateString' => $date,
            'orderCount' => 10,
            'orderValue' => '1000.00',
            'averageOrderValue' => '100.00',
        ]);

        $this->orderSalesSummaryCalculator->process();

        $orderSalesSummary = OrderSalesSummaryFactory::repository()->findOneBy([
            'duration' => SalesDuration::LAST_7->value,
            'dateString' => SalesDuration::LAST_7->getStartDate(),
        ]);

        $this->assertInstanceOf(OrderSalesSummary::class, $orderSalesSummary);

        $this->assertSame(10, $orderSalesSummary->getOrderCount());
        $this->assertSame('1000.00', $orderSalesSummary->getOrderValue());
        $this->assertSame(100, (int) $orderSalesSummary->getAverageOrderValue());
    }
}
