<?php

declare(strict_types=1);

namespace App\Tests\Reporting\Domain\Metric;

use App\Reporting\Domain\Metric\SalesDuration;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;

final class SalesDurationDomainTest extends TestCase
{
    use ClockSensitiveTrait;

    protected function setUp(): void
    {
        self::mockTime('2026-03-15 10:30:00');
    }

    public function testDefaultReturnsLast30(): void
    {
        self::assertSame(SalesDuration::LAST_30, SalesDuration::default());
    }

    #[TestWith(['today', true])]
    #[TestWith(['weekAgo', true])]
    #[TestWith(['last7', true])]
    #[TestWith(['last30', true])]
    #[TestWith(['mtd', true])]
    #[TestWith(['day', true])]
    #[TestWith(['month', true])]
    #[TestWith(['invalid', false])]
    #[TestWith(['', false])]
    #[TestWith(['LAST_30', false])]
    #[TestWith(['weekly', false])]
    public function testIsValid(string $duration, bool $valid): void
    {
        self::assertSame($valid, SalesDuration::isValid($duration));
    }

    /**
     * @return iterable<string, array{SalesDuration, bool, string}>
     */
    public static function startDates(): iterable
    {
        yield 'today' => [SalesDuration::TODAY, false, '2026-03-15'];
        yield 'week ago' => [SalesDuration::WEEK_AGO, false, '2026-03-08'];
        yield 'last 7 days, today included' => [SalesDuration::LAST_7, false, '2026-03-09'];
        yield 'last 30 days, today included' => [SalesDuration::LAST_30, false, '2026-02-14'];
        yield 'month to date' => [SalesDuration::MTD, false, '2026-03-01'];
        yield 'day' => [SalesDuration::DAY, false, '2026-03-15'];
        yield 'day, rebuilding 30 days' => [SalesDuration::DAY, true, '2026-02-14'];
        yield 'month' => [SalesDuration::MONTH, false, '2026-03-01'];
        yield 'month, rebuilding 12 months' => [SalesDuration::MONTH, true, '2025-03-01'];
    }

    #[DataProvider('startDates')]
    public function testGetStartDate(SalesDuration $duration, bool $rebuildRange, string $expected): void
    {
        self::assertSame($expected, $duration->getStartDate($rebuildRange));
    }

    #[TestWith([SalesDuration::WEEK_AGO, '2026-03-08 10:30:00'])]
    #[TestWith([SalesDuration::TODAY, '2026-03-16'])]
    #[TestWith([SalesDuration::LAST_30, '2026-03-16'])]
    public function testGetEndDate(SalesDuration $duration, string $expected): void
    {
        self::assertSame($expected, $duration->getEndDate());
    }

    /**
     * @return iterable<string, array{SalesDuration, string}>
     */
    public static function dateStringFormats(): iterable
    {
        yield 'day' => [SalesDuration::DAY, '%Y-%m-%d'];
        yield 'month' => [SalesDuration::MONTH, '%Y-%m-01'];
        // Aggregated durations group every row under their start date
        yield 'today' => [SalesDuration::TODAY, '2026-03-15'];
        yield 'week ago' => [SalesDuration::WEEK_AGO, '2026-03-08'];
        yield 'last 7 days' => [SalesDuration::LAST_7, '2026-03-09'];
        yield 'last 30 days' => [SalesDuration::LAST_30, '2026-02-14'];
        yield 'month to date' => [SalesDuration::MTD, '2026-03-01'];
    }

    #[DataProvider('dateStringFormats')]
    public function testGetDateStringFormat(SalesDuration $duration, string $expected): void
    {
        self::assertSame($expected, $duration->getDateStringFormat());
    }

    #[TestWith([SalesDuration::MONTH, 'M Y', '+1 month'])]
    #[TestWith([SalesDuration::MTD, 'M Y', '+1 month'])]
    #[TestWith([SalesDuration::TODAY, 'd M', '+1 day'])]
    #[TestWith([SalesDuration::WEEK_AGO, 'd M', '+1 day'])]
    #[TestWith([SalesDuration::LAST_7, 'd M', '+1 day'])]
    #[TestWith([SalesDuration::LAST_30, 'd M', '+1 day'])]
    #[TestWith([SalesDuration::DAY, 'd M', '+1 day'])]
    public function testChartLabelFormatAndGranularity(SalesDuration $duration, string $labelFormat, string $granularity): void
    {
        self::assertSame($labelFormat, $duration->getChartLabelFormat());
        self::assertSame($granularity, $duration->getChartGranularity());
    }

    /**
     * @return iterable<string, array{SalesDuration, bool, ?string}>
     */
    public static function rangeStartDates(): iterable
    {
        yield 'day' => [SalesDuration::DAY, false, '2026-03-15'];
        yield 'month' => [SalesDuration::MONTH, false, '2026-03-01'];
        yield 'day, rebuilding' => [SalesDuration::DAY, true, null];
        yield 'month, rebuilding' => [SalesDuration::MONTH, true, null];
        yield 'today' => [SalesDuration::TODAY, false, null];
        yield 'last 7 days' => [SalesDuration::LAST_7, false, null];
        yield 'last 30 days' => [SalesDuration::LAST_30, false, null];
        yield 'month to date' => [SalesDuration::MTD, false, null];
    }

    #[DataProvider('rangeStartDates')]
    public function testGetRangeStartDate(SalesDuration $duration, bool $rebuildRange, ?string $expected): void
    {
        self::assertSame($expected, $duration->getRangeStartDate($rebuildRange));
    }
}
