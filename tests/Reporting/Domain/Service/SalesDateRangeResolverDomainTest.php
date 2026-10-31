<?php

declare(strict_types=1);

namespace App\Tests\Reporting\Domain\Service;

use App\Reporting\Domain\Metric\SalesDuration;
use App\Reporting\Domain\Service\SalesDateRangeResolver;
use PHPUnit\Framework\TestCase;

final class SalesDateRangeResolverDomainTest extends TestCase
{
    private SalesDateRangeResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new SalesDateRangeResolver();
    }

    public function testMonthToDateIsChartedByMonthFromTheStartOfTheMonthRange(): void
    {
        self::assertSame(SalesDuration::MONTH, $this->resolver->getRangeDuration(SalesDuration::MTD));
        self::assertSame(SalesDuration::MONTH->getStartDate(true), $this->resolver->getRangeStartDate(SalesDuration::MTD));
    }

    public function testOtherDurationsAreChartedByDayFromTheirOwnStart(): void
    {
        self::assertSame(SalesDuration::DAY, $this->resolver->getRangeDuration(SalesDuration::LAST_7));
        self::assertSame(SalesDuration::LAST_7->getStartDate(), $this->resolver->getRangeStartDate(SalesDuration::LAST_7));
    }

    public function testDateRangeHasOneZeroPerStepAndLeavesOutTheEndDate(): void
    {
        $range = $this->resolver->generateDateRange(
            new \DateTimeImmutable('2026-03-30'),
            new \DateTimeImmutable('2026-04-02'),
            'Y-m-d',
            '1 day',
        );

        self::assertSame(['2026-03-30' => 0, '2026-03-31' => 0, '2026-04-01' => 0], $range);
    }

    public function testDateRangeByMonth(): void
    {
        $range = $this->resolver->generateDateRange(
            new \DateTimeImmutable('2026-01-01'),
            new \DateTimeImmutable('2026-04-01'),
            'Y-m',
            '1 month',
        );

        self::assertSame(['2026-01' => 0, '2026-02' => 0, '2026-03' => 0], $range);
    }

    public function testDateRangeIsEmptyWhenStartAndEndAreTheSame(): void
    {
        $date = new \DateTimeImmutable('2026-03-30');

        self::assertSame([], $this->resolver->generateDateRange($date, $date, 'Y-m-d', '1 day'));
    }

    public function testDateRangeFailsWhenStartIsAfterEnd(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('Start date must be less than or equal to end date.');

        $this->resolver->generateDateRange(
            new \DateTimeImmutable('2026-04-02'),
            new \DateTimeImmutable('2026-03-30'),
            'Y-m-d',
            '1 day',
        );
    }
}
