<?php

namespace App\Tests\Integration\Service\Sales;

use App\Reporting\Application\Handler\CalculateOrderSalesHandler;
use App\Reporting\Domain\Model\SalesType\OrderSales;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use tests\Shared\Factory\CustomerOrderItemFactory;
use tests\Shared\Factory\OrderSalesFactory;
use tests\Shared\Factory\ProductFactory;
use Zenstruck\Foundry\Test\Factories;

class OrderSalesCalculatorIntegrationTest extends KernelTestCase
{
    use Factories;

    private CalculateOrderSalesHandler $orderSalesCalculator;

    protected function setUp(): void
    {
        self::bootKernel();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $validator = static::getContainer()->get(ValidatorInterface::class);
        $this->orderSalesCalculator = new CalculateOrderSalesHandler($em, $validator);
    }

    public function testProcessedSuccessfully(): void
    {
        $date = new \DateTime()->format('Y-m-d');
        $product = ProductFactory::createOne([
            'stock' => 100,
            'sellPrice' => 100.00,
        ]);
        CustomerOrderItemFactory::createMany(10, ['product' => $product]);

        $this->orderSalesCalculator->process($date);

        $orderSales = OrderSalesFactory::repository()->findOneBy(['dateString' => $date]);
        $this->assertInstanceOf(OrderSales::class, $orderSales);

        $this->assertSame(10, $orderSales->getOrderCount());
        $this->assertGreaterThan(1000.00, $orderSales->getOrderValue());
        $this->assertGreaterThan(100.00, $orderSales->getAverageOrderValue());
    }
}
