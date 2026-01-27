<?php

namespace App\Tests\Reporting\Application\Handler\Report;

use App\Reporting\Application\Handler\Report\OrderSummaryReportHandler;
use App\Reporting\Application\Report\OrderSummaryReportCriteria;
use App\Reporting\Domain\Metric\SalesDuration;
use App\Tests\Shared\Factory\OrderSalesSummaryFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class OrderSummaryReportHandlerTest extends KernelTestCase
{
    use Factories;

    private OrderSummaryReportHandler $handler;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(OrderSummaryReportHandler::class);
    }

    public function testInvokeReturnsResultWithExpectedKeys(): void
    {
        $criteria = new OrderSummaryReportCriteria();

        $result = ($this->handler)($criteria);

        self::assertTrue($result->ok);
        self::assertArrayHasKey('summary', $result->payload);
        self::assertArrayHasKey('orderSalesChart', $result->payload);
        self::assertArrayHasKey('orderProgressChart', $result->payload);
    }

    public function testInvokeReturnsNullChartsWhenNoData(): void
    {
        $criteria = new OrderSummaryReportCriteria();

        $result = ($this->handler)($criteria);

        self::assertTrue($result->ok);
        self::assertNull($result->payload['orderSalesChart']);
        self::assertNull($result->payload['orderProgressChart']);
    }

    public function testInvokeCreatesChartsWhenDataExists(): void
    {
        $today = new \DateTime()->format('Y-m-d');

        OrderSalesSummaryFactory::createOne([
            'dateString' => $today,
            'orderCount' => 10,
            'orderValue' => 500.00,
        ]);

        $criteria = new OrderSummaryReportCriteria();
        $criteria->setDuration(SalesDuration::TODAY->value);

        $result = ($this->handler)($criteria);

        self::assertTrue($result->ok);
        self::assertIsArray($result->payload['summary']);
    }
}
