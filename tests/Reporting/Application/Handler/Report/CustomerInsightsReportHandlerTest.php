<?php

namespace App\Tests\Reporting\Application\Handler\Report;

use App\Reporting\Application\Handler\Report\CustomerInsightsReportHandler;
use App\Reporting\Application\Report\CustomerInsightsReportCriteria;
use App\Reporting\Domain\Metric\SalesDuration;
use App\Reporting\Domain\Model\SalesType\CustomerSalesType;
use App\Tests\Shared\Factory\CustomerSalesSummaryFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class CustomerInsightsReportHandlerTest extends KernelTestCase
{
    use Factories;

    private CustomerInsightsReportHandler $handler;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(CustomerInsightsReportHandler::class);
    }

    public function testInvokeReturnsResultWithExpectedKeys(): void
    {
        $criteria = new CustomerInsightsReportCriteria();

        $result = ($this->handler)($criteria);

        self::assertTrue($result->ok);
        self::assertArrayHasKey('summary', $result->payload);
        self::assertArrayHasKey('customerChart', $result->payload);
        self::assertArrayHasKey('topCustomers', $result->payload);
    }

    public function testInvokeReturnsNullChartWhenNoData(): void
    {
        $criteria = new CustomerInsightsReportCriteria();

        $result = ($this->handler)($criteria);

        self::assertTrue($result->ok);
        self::assertNull($result->payload['customerChart']);
    }

    public function testInvokeReturnsSummaryWhenDataExists(): void
    {
        $today = new \DateTime()->format('Y-m-d');

        CustomerSalesSummaryFactory::createOne([
            'customerSalesType' => CustomerSalesType::create(SalesDuration::LAST_30),
            'dateString' => $today,
            'totalCustomers' => 100,
            'activeCustomers' => 50,
            'newCustomers' => 10,
            'returningCustomers' => 40,
            'totalRevenue' => '5000.00',
            'averageClv' => '50.00',
            'averageAov' => '100.00',
            'repeatRate' => '40.00',
            'reviewRate' => '10.00',
            'averageOrdersPerCustomer' => '2.50',
        ]);

        $criteria = new CustomerInsightsReportCriteria();
        $criteria->setDuration(SalesDuration::LAST_30->value);

        $result = ($this->handler)($criteria);

        self::assertTrue($result->ok);
    }

    public function testInvokeReturnsTopCustomersAsArray(): void
    {
        $criteria = new CustomerInsightsReportCriteria();

        $result = ($this->handler)($criteria);

        self::assertTrue($result->ok);
        self::assertIsArray($result->payload['topCustomers']);
    }
}
