<?php

namespace App\Tests\Reporting\Unit;

use App\Reporting\Application\Report\OrderSummaryReportCriteria;
use App\Reporting\Domain\Metric\OrderSalesMetric;
use App\Reporting\Domain\Metric\SalesDuration;
use PHPUnit\Framework\TestCase;

class OrderSummaryReportDtoTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $dto = new OrderSummaryReportCriteria();

        $this->assertSame(OrderSalesMetric::COUNT, $dto->getSort());
        $this->assertSame('desc', $dto->getSortDirection());
        $this->assertSame(SalesDuration::LAST_30, $dto->getDuration());
    }

    public function testSetSort(): void
    {
        $dto = new OrderSummaryReportCriteria();
        $dto->setSort(OrderSalesMetric::VALUE->value);

        $this->assertSame(OrderSalesMetric::VALUE, $dto->getSort());
    }

    public function testInvalidSetSort(): void
    {
        $dto = new OrderSummaryReportCriteria();
        $dto->setSort('INVALID_SORT');

        $this->assertSame(OrderSalesMetric::default(), $dto->getSort());
    }

    public function testSetSortDirection(): void
    {
        $dto = new OrderSummaryReportCriteria();
        $dto->setSortDirection('ASC');

        $this->assertSame('asc', $dto->getSortDirection());
    }

    public function testInvalidSetSortDirection(): void
    {
        $dto = new OrderSummaryReportCriteria();
        $dto->setSortDirection('INVALID_DIRECTION');

        $this->assertSame('desc', $dto->getSortDirection());
    }

    public function testSetDuration(): void
    {
        $dto = new OrderSummaryReportCriteria();
        $dto->setDuration(SalesDuration::LAST_7->value);

        $this->assertSame(SalesDuration::LAST_7, $dto->getDuration());
    }

    public function testInvalidSetDuration(): void
    {
        $dto = new OrderSummaryReportCriteria();
        $dto->setDuration('INVALID_DURATION');

        $this->assertSame(SalesDuration::default(), $dto->getDuration());
    }
}
