<?php

namespace App\Tests\Integration\Service\Dashboard;

use App\Order\Domain\Model\Order\OrderStatus;
use App\Reporting\Domain\Metric\OrderSalesMetric;
use App\Reporting\Domain\Metric\SalesDuration;
use App\Reporting\UI\Http\Dashboard\Chart\DoughnutChartBuilder;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\Chartjs\Model\Chart;

class DoughnutChartBuilderIntegrationTest extends KernelTestCase
{
    private DoughnutChartBuilder $doughnutChartBuilder;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->doughnutChartBuilder = static::getContainer()->get(DoughnutChartBuilder::class);
    }

    public function testCreate(): void
    {
        $salesData = [
            ['status' => OrderStatus::SHIPPED, 'orderValue' => 100],
            ['status' => OrderStatus::PROCESSING, 'orderValue' => 200],
            ['status' => OrderStatus::PENDING, 'orderValue' => 300],
        ];
        $salesDuration = SalesDuration::LAST_7;
        $salesMetric = OrderSalesMetric::VALUE;

        $chart = $this->doughnutChartBuilder->create($salesData, $salesDuration, $salesMetric);

        $this->assertInstanceOf(Chart::class, $chart);
        $this->assertSame(Chart::TYPE_DOUGHNUT, $chart->getType());
        $this->assertSame('Value', $chart->getData()['datasets'][0]['label']);
        $this->assertSame('currency', $chart->getOptions()['plugins']['tooltip']['axisType']);
        $this->assertSame([300, 200, 100], $chart->getData()['datasets'][0]['data']);
    }
}
