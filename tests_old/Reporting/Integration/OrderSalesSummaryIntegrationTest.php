<?php

namespace App\Tests\Reporting\Integration;

use App\Reporting\Domain\Metric\SalesDuration;
use App\Reporting\Domain\Model\SalesType\OrderSalesType;
use tests\Shared\Factory\OrderSalesSummaryFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Test\Factories;

class OrderSalesSummaryIntegrationTest extends KernelTestCase
{
    use Factories;

    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    public function testValidOrderSalesSummary(): void
    {
        $orderSalesType = OrderSalesType::create(SalesDuration::LAST_7);
        $orderSalesSummary = OrderSalesSummaryFactory::createOne([
            'orderSalesType' => $orderSalesType,
            'dateString' => '2023-01-01',
            'orderCount' => 10,
            'orderValue' => '100.00',
            'averageOrderValue' => '10.00',
        ]);

        $errors = $this->validator->validate($orderSalesSummary);
        $this->assertCount(0, $errors);
    }

    public function testDateStringIsRequired(): void
    {
        $orderSalesSummary = OrderSalesSummaryFactory::new()->withoutPersisting()->create(['dateString' => '']);

        $violations = $this->validator->validate($orderSalesSummary);
        $this->assertSame('Date string must not be blank', $violations[0]->getMessage());
    }

    public function testInvalidOrderCount(): void
    {
        $orderSalesSummary = OrderSalesSummaryFactory::new()->withoutPersisting()->create(['orderCount' => -1]);

        $violations = $this->validator->validate($orderSalesSummary);
        $this->assertSame('Order count must be zero or positive', $violations[0]->getMessage());
    }

    public function testOrderValueIsRequired(): void
    {
        $orderSalesSummary = OrderSalesSummaryFactory::new()->withoutPersisting()->create(['orderValue' => '']);

        $violations = $this->validator->validate($orderSalesSummary);
        $this->assertSame('Order value must not be blank', $violations[0]->getMessage());
    }

    public function testAverageOrderValueIsRequired(): void
    {
        $orderSalesSummary = OrderSalesSummaryFactory::new()->withoutPersisting()->create(['averageOrderValue' => '']);

        $violations = $this->validator->validate($orderSalesSummary);
        $this->assertSame('Average order value must not be blank', $violations[0]->getMessage());
    }

    public function testOrderSalesSummaryPersistence(): void
    {
        $orderSalesType = OrderSalesType::create(SalesDuration::LAST_7);
        OrderSalesSummaryFactory::createOne([
            'orderSalesType' => $orderSalesType,
            'dateString' => '2023-01-01',
            'orderCount' => 10,
            'orderValue' => '100.00',
        ]);

        $persistedOrderSalesSummary = OrderSalesSummaryFactory::repository()->findOneBy([
            'dateString' => '2023-01-01',
            'duration' => SalesDuration::LAST_7->value,
        ]);

        $this->assertEquals(10, $persistedOrderSalesSummary->getOrderCount());
        $this->assertEquals('100.00', $persistedOrderSalesSummary->getOrderValue());
        $this->assertEquals('10.00', $persistedOrderSalesSummary->getAverageOrderValue());
    }
}
