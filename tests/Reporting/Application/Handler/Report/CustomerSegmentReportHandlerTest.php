<?php

namespace App\Tests\Reporting\Application\Handler\Report;

use App\Reporting\Application\Handler\Report\CustomerSegmentReportHandler;
use App\Reporting\Application\Report\CustomerSegmentReportCriteria;
use App\Reporting\Domain\Metric\CustomerSegment;
use App\Reporting\Domain\Metric\SalesDuration;
use App\Reporting\Domain\Model\SalesType\CustomerSalesType;
use App\Tests\Shared\Factory\CustomerSegmentSummaryFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class CustomerSegmentReportHandlerTest extends KernelTestCase
{
    use Factories;

    private CustomerSegmentReportHandler $handler;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(CustomerSegmentReportHandler::class);
    }

    public function testInvokeReturnsResultWithExpectedKeys(): void
    {
        $criteria = new CustomerSegmentReportCriteria();

        $result = ($this->handler)($criteria);

        self::assertTrue($result->ok);
        self::assertArrayHasKey('summary', $result->payload);
        self::assertArrayHasKey('segmentChart', $result->payload);
        self::assertArrayHasKey('segmentData', $result->payload);
    }

    public function testInvokeReturnsNullChartWhenNoData(): void
    {
        $criteria = new CustomerSegmentReportCriteria();

        $result = ($this->handler)($criteria);

        self::assertTrue($result->ok);
        self::assertNull($result->payload['segmentChart']);
    }

    public function testInvokeReturnsZeroCountsWhenNoData(): void
    {
        $criteria = new CustomerSegmentReportCriteria();

        $result = ($this->handler)($criteria);

        self::assertTrue($result->ok);
        self::assertSame(0, $result->payload['summary']['new']);
        self::assertSame(0, $result->payload['summary']['returning']);
        self::assertSame(0, $result->payload['summary']['loyal']);
        self::assertSame(0, $result->payload['summary']['lapsed']);
    }

    public function testInvokeReturnsSummaryWhenDataExists(): void
    {
        $startDate = SalesDuration::LAST_30->getStartDate();

        foreach (CustomerSegment::cases() as $segment) {
            CustomerSegmentSummaryFactory::createOne([
                'customerSalesType' => CustomerSalesType::create(SalesDuration::LAST_30),
                'segment' => $segment,
                'dateString' => $startDate,
                'customerCount' => 25,
                'orderCount' => 50,
                'orderValue' => '2500.00',
                'averageOrderValue' => '50.00',
                'averageItemsPerOrder' => '2.50',
            ]);
        }

        $criteria = new CustomerSegmentReportCriteria();
        $criteria->setDuration(SalesDuration::LAST_30->value);

        $result = ($this->handler)($criteria);

        self::assertTrue($result->ok);
        self::assertSame(25, $result->payload['summary']['new']);
        self::assertSame(25, $result->payload['summary']['returning']);
        self::assertSame(25, $result->payload['summary']['loyal']);
        self::assertSame(25, $result->payload['summary']['lapsed']);
        self::assertNotNull($result->payload['segmentChart']);
    }
}
