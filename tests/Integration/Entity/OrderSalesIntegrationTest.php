<?php

namespace App\Tests\Integration\Entity;

use App\Factory\OrderSalesFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Test\Factories;

class OrderSalesIntegrationTest extends KernelTestCase
{
    use Factories;

    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    public function testValidOrderSales(): void
    {
        $orderSales = OrderSalesFactory::createOne([
            'dateString' => '2023-01-01',
            'orderCount' => 10,
            'orderValue' => '100.00',
            'averageOrderValue' => '10.00',
        ]);

        $errors = $this->validator->validate($orderSales);
        $this->assertCount(0, $errors);
    }

    public function testDateStringIsRequired(): void
    {
        $orderSales = OrderSalesFactory::new()->withoutPersisting()->create(['dateString' => '']);

        $violations = $this->validator->validate($orderSales);
        $this->assertSame('Date string must not be blank', $violations[0]->getMessage());
    }

    public function testInvalidOrderCount(): void
    {
        $orderSales = OrderSalesFactory::new()->withoutPersisting()->create(['orderCount' => -1]);

        $violations = $this->validator->validate($orderSales);
        $this->assertSame('Order count must be zero or positive', $violations[0]->getMessage());
    }

    public function testOrderValueIsRequired(): void
    {
        $orderSales = OrderSalesFactory::new()->withoutPersisting()->create(['orderValue' => '']);

        $violations = $this->validator->validate($orderSales);
        $this->assertSame('Order value must not be blank', $violations[0]->getMessage());
    }

    public function testAverageOrderValueIsRequired(): void
    {
        $orderSales = OrderSalesFactory::new()->withoutPersisting()->create(['averageOrderValue' => '']);

        $violations = $this->validator->validate($orderSales);
        $this->assertSame('Average order value must not be blank', $violations[0]->getMessage());
    }

    public function testOrderSalesPersistence(): void
    {
        OrderSalesFactory::createOne([
            'dateString' => '2023-01-01',
            'orderCount' => 10,
            'orderValue' => '100.00',
        ]);

        $persistedOrderSales = OrderSalesFactory::repository()->findOneBy([
            'dateString' => '2023-01-01'
        ]);

        $this->assertEquals(10, $persistedOrderSales->getOrderCount());
        $this->assertEquals('100.00', $persistedOrderSales->getOrderValue());
        $this->assertEquals('10', $persistedOrderSales->getAverageOrderValue());
    }
}
