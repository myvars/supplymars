<?php

namespace App\Tests\Reporting\UI\Http\Chart;

use App\Order\Domain\Model\Order\OrderStatus;
use App\Reporting\Domain\Metric\OrderSalesMetric;
use App\Reporting\UI\Http\Chart\ChartColorProviderInterface;
use App\Reporting\UI\Http\Chart\OrderProgressChartBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class OrderProgressChartBuilderTest extends TestCase
{
    public function testCreateReturnsChartWithCorrectType(): void
    {
        $chart = new Chart(Chart::TYPE_DOUGHNUT);
        $chartBuilder = $this->createStub(ChartBuilderInterface::class);
        $chartBuilder->method('createChart')
            ->with(Chart::TYPE_DOUGHNUT)
            ->willReturn($chart);

        $colorProvider = $this->createStub(ChartColorProviderInterface::class);
        $colorProvider->method('getColor')->willReturn('#ffffff');
        $colorProvider->method('getSortOrder')->willReturn(1);

        $builder = new OrderProgressChartBuilder($chartBuilder, $colorProvider);

        $salesData = [
            ['status' => OrderStatus::PENDING, 'orderCount' => 5],
        ];

        $result = $builder->create($salesData, OrderSalesMetric::COUNT);

        self::assertSame($chart, $result);
    }

    public function testCreateSortsDataByColorProviderOrder(): void
    {
        $chart = new Chart(Chart::TYPE_DOUGHNUT);
        $chartBuilder = $this->createStub(ChartBuilderInterface::class);
        $chartBuilder->method('createChart')
            ->with(Chart::TYPE_DOUGHNUT)
            ->willReturn($chart);

        $colorProvider = $this->createStub(ChartColorProviderInterface::class);
        $colorProvider->method('getColor')->willReturn('#ffffff');
        $colorProvider->method('getSortOrder')
            ->willReturnCallback(fn (string $key): int => match ($key) {
                OrderStatus::PENDING->value => 1,
                OrderStatus::PROCESSING->value => 2,
                OrderStatus::DELIVERED->value => 4,
                default => 0,
            });

        $builder = new OrderProgressChartBuilder($chartBuilder, $colorProvider);

        $salesData = [
            ['status' => OrderStatus::DELIVERED, 'orderCount' => 3],
            ['status' => OrderStatus::PENDING, 'orderCount' => 5],
            ['status' => OrderStatus::PROCESSING, 'orderCount' => 2],
        ];

        $result = $builder->create($salesData, OrderSalesMetric::COUNT);

        $data = $result->getData();
        self::assertArrayHasKey('labels', $data);

        $labels = $data['labels'];
        self::assertSame(OrderStatus::PENDING->value, $labels[0]);
        self::assertSame(OrderStatus::PROCESSING->value, $labels[1]);
        self::assertSame(OrderStatus::DELIVERED->value, $labels[2]);
    }

    public function testCreateAppliesColorsFromProvider(): void
    {
        $chart = new Chart(Chart::TYPE_DOUGHNUT);
        $chartBuilder = $this->createStub(ChartBuilderInterface::class);
        $chartBuilder->method('createChart')
            ->with(Chart::TYPE_DOUGHNUT)
            ->willReturn($chart);

        $colorProvider = $this->createStub(ChartColorProviderInterface::class);
        $colorProvider->method('getColor')
            ->willReturnCallback(fn (string $key): string => match ($key) {
                OrderStatus::PENDING->value => '#ff0000',
                OrderStatus::PROCESSING->value => '#00ff00',
                default => '#000000',
            });
        $colorProvider->method('getSortOrder')->willReturn(1);

        $builder = new OrderProgressChartBuilder($chartBuilder, $colorProvider);

        $salesData = [
            ['status' => OrderStatus::PENDING, 'orderCount' => 5],
            ['status' => OrderStatus::PROCESSING, 'orderCount' => 2],
        ];

        $result = $builder->create($salesData, OrderSalesMetric::COUNT);

        $data = $result->getData();
        $colors = $data['datasets'][0]['backgroundColor'] ?? [];

        self::assertContains('#ff0000', $colors);
        self::assertContains('#00ff00', $colors);
    }
}
