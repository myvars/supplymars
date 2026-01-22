<?php

namespace App\Tests\Reporting\Domain\Metric;

use App\Reporting\Domain\Metric\SalesDuration;
use PHPUnit\Framework\TestCase;

class SalesDurationTest extends TestCase
{
    public function testDefaultReturnsLast30(): void
    {
        self::assertSame(SalesDuration::LAST_30, SalesDuration::default());
    }

    public function testIsValidReturnsTrueForValidDuration(): void
    {
        self::assertTrue(SalesDuration::isValid('today'));
        self::assertTrue(SalesDuration::isValid('weekAgo'));
        self::assertTrue(SalesDuration::isValid('last7'));
        self::assertTrue(SalesDuration::isValid('last30'));
        self::assertTrue(SalesDuration::isValid('mtd'));
        self::assertTrue(SalesDuration::isValid('day'));
        self::assertTrue(SalesDuration::isValid('month'));
    }

    public function testIsValidReturnsFalseForInvalidDuration(): void
    {
        self::assertFalse(SalesDuration::isValid('invalid'));
        self::assertFalse(SalesDuration::isValid(''));
        self::assertFalse(SalesDuration::isValid('LAST_30'));
        self::assertFalse(SalesDuration::isValid('weekly'));
    }

    public function testGetStartDateForToday(): void
    {
        $duration = SalesDuration::TODAY;
        $expected = new \DateTime()->format('Y-m-d');

        self::assertSame($expected, $duration->getStartDate());
    }

    public function testGetStartDateForLast7(): void
    {
        $duration = SalesDuration::LAST_7;
        $expected = new \DateTime()->modify('-6 days')->format('Y-m-d');

        self::assertSame($expected, $duration->getStartDate());
    }

    public function testGetStartDateForLast30(): void
    {
        $duration = SalesDuration::LAST_30;
        $expected = new \DateTime()->modify('-29 days')->format('Y-m-d');

        self::assertSame($expected, $duration->getStartDate());
    }

    public function testGetStartDateForMtd(): void
    {
        $duration = SalesDuration::MTD;
        $expected = new \DateTime()->format('Y-m-01');

        self::assertSame($expected, $duration->getStartDate());
    }

    public function testGetEndDateForWeekAgoReturnsSevenDaysAgo(): void
    {
        $duration = SalesDuration::WEEK_AGO;
        $result = $duration->getEndDate();
        $expectedDate = new \DateTime()->modify('-7 days')->format('Y-m-d');

        self::assertStringStartsWith($expectedDate, $result);
    }

    public function testGetEndDateForDefaultReturnsTomorrow(): void
    {
        $duration = SalesDuration::TODAY;
        $expected = new \DateTime('+1 day')->format('Y-m-d');

        self::assertSame($expected, $duration->getEndDate());
    }

    public function testGetDateStringFormatForDay(): void
    {
        $duration = SalesDuration::DAY;

        self::assertSame('%Y-%m-%d', $duration->getDateStringFormat());
    }

    public function testGetDateStringFormatForMonth(): void
    {
        $duration = SalesDuration::MONTH;

        self::assertSame('%Y-%m-01', $duration->getDateStringFormat());
    }

    public function testGetDateStringFormatForAggregatedDurationsReturnsStartDate(): void
    {
        self::assertSame(SalesDuration::TODAY->getStartDate(), SalesDuration::TODAY->getDateStringFormat());
        self::assertSame(SalesDuration::LAST_7->getStartDate(), SalesDuration::LAST_7->getDateStringFormat());
        self::assertSame(SalesDuration::LAST_30->getStartDate(), SalesDuration::LAST_30->getDateStringFormat());
        self::assertSame(SalesDuration::MTD->getStartDate(), SalesDuration::MTD->getDateStringFormat());
    }

    public function testGetChartLabelFormatForMonthReturnsMonthYear(): void
    {
        self::assertSame('M Y', SalesDuration::MONTH->getChartLabelFormat());
        self::assertSame('M Y', SalesDuration::MTD->getChartLabelFormat());
    }

    public function testGetChartLabelFormatForOtherDurationsReturnsDayMonth(): void
    {
        self::assertSame('d M', SalesDuration::TODAY->getChartLabelFormat());
        self::assertSame('d M', SalesDuration::LAST_7->getChartLabelFormat());
        self::assertSame('d M', SalesDuration::LAST_30->getChartLabelFormat());
        self::assertSame('d M', SalesDuration::DAY->getChartLabelFormat());
    }

    public function testGetChartGranularityForMonthReturnsOneMonth(): void
    {
        self::assertSame('+1 month', SalesDuration::MONTH->getChartGranularity());
        self::assertSame('+1 month', SalesDuration::MTD->getChartGranularity());
    }

    public function testGetChartGranularityForOtherDurationsReturnsOneDay(): void
    {
        self::assertSame('+1 day', SalesDuration::TODAY->getChartGranularity());
        self::assertSame('+1 day', SalesDuration::LAST_7->getChartGranularity());
        self::assertSame('+1 day', SalesDuration::LAST_30->getChartGranularity());
        self::assertSame('+1 day', SalesDuration::DAY->getChartGranularity());
    }

    public function testGetRangeStartDateReturnsNullForNonRebuildAggregatedDurations(): void
    {
        self::assertNull(SalesDuration::TODAY->getRangeStartDate(false));
        self::assertNull(SalesDuration::LAST_7->getRangeStartDate(false));
        self::assertNull(SalesDuration::LAST_30->getRangeStartDate(false));
        self::assertNull(SalesDuration::MTD->getRangeStartDate(false));
    }

    public function testGetRangeStartDateReturnsStartDateForDayAndMonthWithoutRebuild(): void
    {
        self::assertSame(SalesDuration::DAY->getStartDate(false), SalesDuration::DAY->getRangeStartDate(false));
        self::assertSame(SalesDuration::MONTH->getStartDate(false), SalesDuration::MONTH->getRangeStartDate(false));
    }

    public function testGetStartDateForDayWithRebuildRangeReturns29DaysAgo(): void
    {
        $duration = SalesDuration::DAY;
        $expected = new \DateTime()->modify('-29 days')->format('Y-m-d');

        self::assertSame($expected, $duration->getStartDate(true));
    }

    public function testGetStartDateForMonthWithRebuildRangeReturns12MonthsAgo(): void
    {
        $duration = SalesDuration::MONTH;
        $expected = new \DateTime()->modify('-12 months')->format('Y-m-01');

        self::assertSame($expected, $duration->getStartDate(true));
    }
}
