<?php

namespace App\Tests\Unit\Service\Dashboard;

use App\Enum\OrderStatus;
use App\Enum\SalesDuration;
use App\Enum\SalesMetricInterface;
use App\Service\Dashboard\DoughnutChartBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class DoughnutChartBuilderTest extends TestCase
{
    private ChartBuilderInterface $chartBuilderMock;
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