<?php

namespace App\Tests\Unit\Service\Dashboard;

use App\Order\Domain\Model\Order\OrderStatus;
use App\Reporting\Domain\Metric\SalesDuration;
use App\Reporting\Domain\Metric\SalesMetricInterface;
use App\Reporting\UI\Http\Dashboard\Chart\DoughnutChartBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class DoughnutChartBuilderTest extends TestCase
{
    private MockObject $chartBuilderMock;

    private DoughnutChartBuilder $doughnutChartBuilder;

    protected function setUp(): void
    {
        $this->chartBuilderMock = $this->createMock(ChartBuilderInterface::class);
        $this->doughnutChartBuilder = new DoughnutChartBuilder($this->chartBuilderMock);
    }

    public function testCreate(): void
    {
        $salesData = [
            ['status' => OrderStatus::SHIPPED, 'orderValue' => 100],
            ['status' => OrderStatus::PROCESSING, 'orderValue' => 200],
        ];
        $salesDuration = SalesDuration::DAY;
        $salesMetric = $this->createMock(SalesMetricInterface::class);

        $salesMetric->method('getValue')->willReturn('orderValue');
        $salesMetric->method('getDataLabel')->willReturn('Value');
        $salesMetric->method('getChartColors')->willReturn(['color' => '#000000', 'borderColor' => '#FFFFFF']);
        $salesMetric->method('getYAxisType')->willReturn('currency');

        $chartMock = $this->createMock(Chart::class);
        $this->chartBuilderMock->method('createChart')->willReturn($chartMock);

        $chart = $this->doughnutChartBuilder->create($salesData, $salesDuration, $salesMetric);

        $this->assertInstanceOf(Chart::class, $chart);
    }
}
