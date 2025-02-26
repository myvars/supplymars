<?php

namespace App\Tests\Unit\DTO;

use App\DTO\OrderSummaryReportDto;
use App\Enum\OrderSalesMetric;
use App\Enum\SalesDuration;
use PHPUnit\Framework\TestCase;

class OrderSummaryReportDtoTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $dto = new OrderSummaryReportDto();

        $this->assertSame(OrderSalesMetric::COUNT, $dto->getSort());
        $this->assertSame('desc', $dto->getSortDirection());
        $this->assertSame(SalesDuration::LAST_30, $dto->getDuration());
    }

    public function testSetSort(): void
    {
        $dto = new OrderSummaryReportDto();
        $dto->setSort(OrderSalesMetric::VALUE->value);

        $this->assertSame(OrderSalesMetric::VALUE, $dto->getSort());
    }

    public function testInvalidSetSort(): void
    {
        $dto = new OrderSummaryReportDto();
        $dto->setSort('INVALID_SORT');

        $this->assertSame(OrderSalesMetric::default(), $dto->getSort());
    }

    public function testSetSortDirection(): void
    {
        $dto = new OrderSummaryReportDto();
        $dto->setSortDirection('ASC');

        $this->assertSame('asc', $dto->getSortDirection());
    }

    public function testInvalidSetSortDirection(): void
    {
        $dto = new OrderSummaryReportDto();
        $dto->setSortDirection('INVALID_DIRECTION');

        $this->assertSame('desc', $dto->getSortDirection());
    }

    public function testSetDuration(): void
    {
        $dto = new OrderSummaryReportDto();
        $dto->setDuration(SalesDuration::LAST_7->value);

        $this->assertSame(SalesDuration::LAST_7, $dto->getDuration());
    }

    public function testInvalidSetDuration(): void
    {
        $dto = new OrderSummaryReportDto();
        $dto->setDuration('INVALID_DURATION');

        $this->assertSame(SalesDuration::default(), $dto->getDuration());
    }
}