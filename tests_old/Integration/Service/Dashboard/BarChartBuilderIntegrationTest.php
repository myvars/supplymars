<?php

namespace App\Tests\Integration\Service\Dashboard;

use App\Reporting\Domain\Metric\OrderSalesMetric;
use App\Reporting\Domain\Metric\ProductSalesMetric;
use App\Reporting\Domain\Metric\SalesDuration;
use App\Reporting\UI\Http\Dashboard\Chart\BarChartBuilder;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\Chartjs\Model\Chart;

class BarChartBuilderIntegrationTest extends KernelTestCase
{
    private BarChartBuilder $barChartBuilder;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->barChartBuilder = static::getContainer()->get(BarChartBuilder::class);
    }

    public function testCreateWithOrderSalesMetric(): void
    {
        $salesData = [
            ['salesDate' => new \DateTimeImmutable(), 'orderValue' => 100],
            ['salesDate' => new \DateTimeImmutable('-1 day'), 'orderValue' => 200],
            ['salesDate' => new \DateTimeImmutable('-2 day'), 'orderValue' => 300],
        ];
        $salesDuration = SalesDuration::LAST_7;
        $salesMetric = OrderSalesMetric::VALUE;

        $chart = $this->barChartBuilder->create($salesData, $salesDuration, $salesMetric);

        $this->assertInstanceOf(Chart::class, $chart);
        $this->assertSame(Chart::TYPE_BAR, $chart->getType());
        $this->assertSame('Value', $chart->getData()['datasets'][0]['label']);
        $this->assertSame('currency', $chart->getOptions()['scales']['y']['grid']['axisType']);
        $this->assertSame([0, 0, 0, 0, 300, 200, 100], $chart->getData()['datasets'][0]['data']);
    }

    public function testCreateWithProductSalesMetric(): void
    {
        $salesData = [
            ['salesDate' => new \DateTimeImmutable(), 'salesMargin' => 5.100],
            ['salesDate' => new \DateTimeImmutable('-1 day'), 'salesMargin' => 4.800],
            ['salesDate' => new \DateTimeImmutable('-2 day'), 'salesMargin' => 5.500],
        ];
        $salesDuration = SalesDuration::LAST_7;
        $salesMetric = ProductSalesMetric::MARGIN;

        $chart = $this->barChartBuilder->create($salesData, $salesDuration, $salesMetric);

        $this->assertInstanceOf(Chart::class, $chart);
        $this->assertSame(Chart::TYPE_BAR, $chart->getType());
        $this->assertSame('Margin', $chart->getData()['datasets'][0]['label']);
        $this->assertSame('percentage', $chart->getOptions()['scales']['y']['grid']['axisType']);
        $this->assertSame([0, 0, 0, 0, 5.500, 4.800, 5.100], $chart->getData()['datasets'][0]['data']);
    }
}
