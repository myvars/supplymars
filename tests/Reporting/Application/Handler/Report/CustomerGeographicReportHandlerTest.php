<?php

namespace App\Tests\Reporting\Application\Handler\Report;

use App\Reporting\Application\Handler\Report\CustomerGeographicReportHandler;
use App\Reporting\Application\Report\CustomerGeographicReportCriteria;
use App\Reporting\Domain\Metric\SalesDuration;
use App\Reporting\Domain\Model\SalesType\CustomerSalesType;
use App\Tests\Shared\Factory\CustomerGeographicSummaryFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class CustomerGeographicReportHandlerTest extends KernelTestCase
{
    use Factories;

    private CustomerGeographicReportHandler $handler;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(CustomerGeographicReportHandler::class);
    }

    public function testInvokeReturnsResultWithExpectedKeys(): void
    {
        $criteria = new CustomerGeographicReportCriteria();

        $result = ($this->handler)($criteria);

        self::assertTrue($result->ok);
        self::assertArrayHasKey('summary', $result->payload);
        self::assertArrayHasKey('geoChart', $result->payload);
        self::assertArrayHasKey('geoData', $result->payload);
    }

    public function testInvokeReturnsNullChartWhenNoData(): void
    {
        $criteria = new CustomerGeographicReportCriteria();

        $result = ($this->handler)($criteria);

        self::assertTrue($result->ok);
        self::assertNull($result->payload['geoChart']);
    }

    public function testInvokeReturnsEmptySummaryWhenNoData(): void
    {
        $criteria = new CustomerGeographicReportCriteria();

        $result = ($this->handler)($criteria);

        self::assertTrue($result->ok);
        self::assertSame('-', $result->payload['summary']['topCity']);
        self::assertSame(0, $result->payload['summary']['cityCount']);
        self::assertSame('0.00', $result->payload['summary']['avgRevenuePerCity']);
    }

    public function testInvokeReturnsSummaryWhenDataExists(): void
    {
        $startDate = SalesDuration::LAST_30->getStartDate();

        CustomerGeographicSummaryFactory::createOne([
            'customerSalesType' => CustomerSalesType::create(SalesDuration::LAST_30),
            'city' => 'Olympus Mons',
            'dateString' => $startDate,
            'customerCount' => 50,
            'orderCount' => 100,
            'orderValue' => '5000.00',
            'averageOrderValue' => '50.00',
        ]);

        CustomerGeographicSummaryFactory::createOne([
            'customerSalesType' => CustomerSalesType::create(SalesDuration::LAST_30),
            'city' => 'Valles Marineris',
            'dateString' => $startDate,
            'customerCount' => 30,
            'orderCount' => 60,
            'orderValue' => '3000.00',
            'averageOrderValue' => '50.00',
        ]);

        $criteria = new CustomerGeographicReportCriteria();
        $criteria->setDuration(SalesDuration::LAST_30->value);

        $result = ($this->handler)($criteria);

        self::assertTrue($result->ok);
        self::assertSame(2, $result->payload['summary']['cityCount']);
        self::assertNotNull($result->payload['geoChart']);
    }
}
