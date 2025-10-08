<?php

namespace App\Tests\Integration\Service\Sales;

use App\Entity\OrderSales;
use App\Factory\CustomerOrderItemFactory;
use App\Factory\OrderSalesFactory;
use App\Factory\ProductFactory;
use App\Service\Sales\OrderSalesCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Test\Factories;

class OrderSalesCalculatorIntegrationTest extends KernelTestCase
{
    use Factories;

    private OrderSalesCalculator $orderSalesCalculator;

    protected function setUp(): void
    {
        self::bootKernel();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $validator = static::getContainer()->get(ValidatorInterface::class);
        $this->orderSalesCalculator = new OrderSalesCalculator($entityManager, $validator);
    }

    public function testProcessedSuccessfully(): void
    {
        $date = new \DateTime()->format('Y-m-d');
        $product = ProductFactory::createOne([
            'stock' => 100,
            'sellPrice' => 100.00
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