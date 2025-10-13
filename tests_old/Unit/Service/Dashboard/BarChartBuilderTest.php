<?php

namespace App\Tests\Unit\Service\Dashboard;

use App\Reporting\Domain\Metric\SalesDuration;
use App\Reporting\Domain\Metric\SalesMetricInterface;
use App\Reporting\UI\Http\Dashboard\Chart\BarChartBuilder;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class BarChartBuilderTest extends TestCase
{
    private MockObject $chartBuilder;

    private BarChartBuilder $barChartBuilder;

    protected function setUp(): void
    {
        $this->chartBuilder = $this->createMock(ChartBuilderInterface::class);
        $this->barChartBuilder = new BarChartBuilder($this->chartBuilder);
    }

    public function testCreate(): void
    {
        $salesData = [
            ['salesDate' => new DateTimeImmutable('2023-10-01'), 'orderValue' => 100],
            ['salesDate' => new DateTimeImmutable('2023-10-02'), 'orderValue' => 200],
        ];
        $salesDuration = SalesDuration::DAY;
        $salesMetric = $this->createMock(SalesMetricInterface::class);

        $salesMetric->method('getValue')->willReturn('orderValue');
        $salesMetric->method('getDataLabel')->willReturn('Value');
        $salesMetric->method('getChartColors')->willReturn(['color' => '#000000', 'borderColor' => '#FFFFFF']);
        $salesMetric->method('getYAxisType')->willReturn('currency');

        $chartMock = $this->createMock(Chart::class);
        $this->chartBuilder->method('createChart')->willReturn($chartMock);

        $chart = $this->barChartBuilder->create($salesData, $salesDuration, $salesMetric);

        $this->assertInstanceOf(Chart::class, $chart);
    }

    public function testGetSalesRangeDuration(): void
    {
        $this->assertSame(SalesDuration::MONTH, BarChartBuilder::getSalesRangeDuration(SalesDuration::MTD));
        $this->assertSame(SalesDuration::DAY, BarChartBuilder::getSalesRangeDuration(SalesDuration::DAY));
    }

    public function testGetSalesRangeStartDate(): void
    {
        $this->assertSame(SalesDuration::MONTH->getStartDate(true), BarChartBuilder::getSalesRangeStartDate(SalesDuration::MTD));
        $this->assertSame(SalesDuration::DAY->getStartDate(), BarChartBuilder::getSalesRangeStartDate(SalesDuration::DAY));
    }
}
