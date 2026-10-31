<?php

declare(strict_types=1);

namespace App\Tests\Audit\Domain;

use App\Audit\Domain\Model\StockChange\HistoryDuration;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

final class HistoryDurationDomainTest extends TestCase
{
    #[TestWith([HistoryDuration::LAST_7, 7])]
    #[TestWith([HistoryDuration::LAST_30, 30])]
    #[TestWith([HistoryDuration::LAST_90, 90])]
    public function testSinceDateIsThatManyDaysAgo(HistoryDuration $duration, int $days): void
    {
        $since = $duration->getSinceDate();
        // Read after the since date, so the gap is never short of whole days
        $now = new \DateTimeImmutable();

        self::assertInstanceOf(\DateTimeImmutable::class, $since);
        self::assertSame($days, $since->diff($now)->days);
    }

    public function testAllHasNoSinceDate(): void
    {
        self::assertNull(HistoryDuration::ALL->getSinceDate());
    }
}
