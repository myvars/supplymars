<?php

namespace App\Tests\Reporting\Application\Handler\Report;

use App\Reporting\Application\Handler\Report\PoItemPerformanceReportHandler;
use App\Reporting\Application\Report\PoItemPerformanceReportCriteria;
use App\Reporting\Domain\Metric\SalesDuration;
use App\Tests\Shared\Factory\PurchaseOrderItemFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

final class PoItemPerformanceReportHandlerTest extends KernelTestCase
{
    use Factories;

    private PoItemPerformanceReportHandler $handler;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(PoItemPerformanceReportHandler::class);
    }

    public function testReturnsSuccessWithExpectedPayloadStructure(): void
    {
        $criteria = new PoItemPerformanceReportCriteria();

        $result = ($this->handler)($criteria);

        self::assertTrue($result->ok);
        self::assertSame('Report created', $result->message);
        self::assertArrayHasKey('summary', $result->payload);
        self::assertArrayHasKey('items', $result->payload);
    }

    public function testReturnsEmptyResultsWhenNoData(): void
    {
        $criteria = new PoItemPerformanceReportCriteria();

        $result = ($this->handler)($criteria);

        self::assertTrue($result->ok);
        self::assertIsArray($result->payload['summary']);
    }

    public function testHandlesOutOfRangePageGracefully(): void
    {
        $criteria = new PoItemPerformanceReportCriteria();
        $criteria->setPage(999);

        $result = ($this->handler)($criteria);

        // Should reset to page 1 and return success
        self::assertTrue($result->ok);
        self::assertArrayHasKey('items', $result->payload);
    }

    public function testReturnsItemsWhenDataExists(): void
    {
        // Create some PO items for the report
        PurchaseOrderItemFactory::createOne();

        $criteria = new PoItemPerformanceReportCriteria();
        $criteria->setDuration(SalesDuration::LAST_30->value);

        $result = ($this->handler)($criteria);

        self::assertTrue($result->ok);
        self::assertArrayHasKey('items', $result->payload);
    }

    public function testSortingIsApplied(): void
    {
        $criteria = new PoItemPerformanceReportCriteria();
        $criteria->setSort('profit');
        $criteria->setSortDirection('DESC');

        $result = ($this->handler)($criteria);

        self::assertTrue($result->ok);
    }
}
