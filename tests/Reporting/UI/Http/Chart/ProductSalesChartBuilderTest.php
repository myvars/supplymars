<?php

namespace App\Tests\Reporting\UI\Http\Chart;

use App\Reporting\Domain\Metric\OrderSalesMetric;
use App\Reporting\Domain\Metric\SalesDuration;
use App\Reporting\Domain\Service\SalesDateRangeResolver;
use App\Reporting\UI\Http\Chart\ProductSalesChartBuilder;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class ProductSalesChartBuilderTest extends TestCase
{
    public function testCreateThrowsWhenDurationIsNull(): void
    {
        $chartBuilder = $this->createStub(ChartBuilderInterface::class);
        $dateRangeResolver = new SalesDateRangeResolver();
        $clock = $this->createStub(ClockInterface::class);
        $clock->method('now')->willReturn(new \DateTimeImmutable());

        $builder = new ProductSalesChartBuilder($chartBuilder, $dateRangeResolver, $clock);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('SalesDuration is required for bar charts.');

        $builder->create([], OrderSalesMetric::COUNT);
    }

    public function testCreateReturnsChartWithCorrectType(): void
    {
        $now = new \DateTimeImmutable();
        $chart = new Chart(Chart::TYPE_BAR);
        $chartBuilder = $this->createStub(ChartBuilderInterface::class);
        $chartBuilder->method('createChart')->willReturn($chart);

        $dateRangeResolver = new SalesDateRangeResolver();
        $clock = $this->createStub(ClockInterface::class);
        $clock->method('now')->willReturn($now);

        $builder = new ProductSalesChartBuilder($chartBuilder, $dateRangeResolver, $clock);

        $result = $builder->create([], OrderSalesMetric::COUNT, SalesDuration::LAST_7);

        self::assertSame($chart, $result);
    }

    public function testCreateZeroFillsMissingDates(): void
    {
        $now = new \DateTimeImmutable();
        $chart = new Chart(Chart::TYPE_BAR);
        $chartBuilder = $this->createStub(ChartBuilderInterface::class);
        $chartBuilder->method('createChart')->willReturn($chart);

        $dateRangeResolver = new SalesDateRangeResolver();
        $clock = $this->createStub(ClockInterface::class);
        $clock->method('now')->willReturn($now);

        $builder = new ProductSalesChartBuilder($chartBuilder, $dateRangeResolver, $clock);

        $twoDaysAgo = $now->modify('-2 days');
        $fiveDaysAgo = $now->modify('-5 days');

        $salesData = [
            ['salesDate' => \DateTime::createFromImmutable($twoDaysAgo), 'orderCount' => 5],
            ['salesDate' => \DateTime::createFromImmutable($fiveDaysAgo), 'orderCount' => 10],
        ];

        $result = $builder->create($salesData, OrderSalesMetric::COUNT, SalesDuration::LAST_7);

        $data = $result->getData();
        self::assertArrayHasKey('datasets', $data);
        self::assertNotEmpty($data['datasets']);

        $values = $data['datasets'][0]['data'] ?? [];
        self::assertContains(0, $values);
    }
}
