<?php

namespace App\Tests\Review\Domain;

use App\Review\Domain\Model\Review\ReviewStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ReviewStatusTransitionTest extends TestCase
{
    #[DataProvider('transitionProvider')]
    public function testCanTransitionTo(ReviewStatus $from, ReviewStatus $to, bool $expected): void
    {
        self::assertSame($expected, $from->canTransitionTo($to));
    }

    /**
     * @return iterable<string, array{ReviewStatus, ReviewStatus, bool}>
     */
    public static function transitionProvider(): iterable
    {
        // PENDING → can transition to PUBLISHED or REJECTED only
        yield 'PENDING → PUBLISHED' => [ReviewStatus::PENDING, ReviewStatus::PUBLISHED, true];
        yield 'PENDING → REJECTED' => [ReviewStatus::PENDING, ReviewStatus::REJECTED, true];
        yield 'PENDING → HIDDEN' => [ReviewStatus::PENDING, ReviewStatus::HIDDEN, false];
        yield 'PENDING → PENDING' => [ReviewStatus::PENDING, ReviewStatus::PENDING, false];

        // PUBLISHED → can transition to HIDDEN only
        yield 'PUBLISHED → HIDDEN' => [ReviewStatus::PUBLISHED, ReviewStatus::HIDDEN, true];
        yield 'PUBLISHED → REJECTED' => [ReviewStatus::PUBLISHED, ReviewStatus::REJECTED, false];
        yield 'PUBLISHED → PENDING' => [ReviewStatus::PUBLISHED, ReviewStatus::PENDING, false];
        yield 'PUBLISHED → PUBLISHED' => [ReviewStatus::PUBLISHED, ReviewStatus::PUBLISHED, false];

        // HIDDEN → can transition to PUBLISHED only
        yield 'HIDDEN → PUBLISHED' => [ReviewStatus::HIDDEN, ReviewStatus::PUBLISHED, true];
        yield 'HIDDEN → REJECTED' => [ReviewStatus::HIDDEN, ReviewStatus::REJECTED, false];
        yield 'HIDDEN → PENDING' => [ReviewStatus::HIDDEN, ReviewStatus::PENDING, false];

        // REJECTED → terminal state
        foreach (ReviewStatus::cases() as $status) {
            yield 'REJECTED → ' . $status->name => [ReviewStatus::REJECTED, $status, false];
        }
    }

    #[DataProvider('allowEditProvider')]
    public function testAllowEdit(ReviewStatus $status, bool $expected): void
    {
        self::assertSame($expected, $status->allowEdit());
    }

    /**
     * @return iterable<string, array{ReviewStatus, bool}>
     */
    public static function allowEditProvider(): iterable
    {
        yield 'PENDING' => [ReviewStatus::PENDING, true];
        yield 'PUBLISHED' => [ReviewStatus::PUBLISHED, true];
        yield 'REJECTED' => [ReviewStatus::REJECTED, false];
        yield 'HIDDEN' => [ReviewStatus::HIDDEN, false];
    }

    public function testGetLevelOrdering(): void
    {
        self::assertLessThan(ReviewStatus::PUBLISHED->getLevel(), ReviewStatus::PENDING->getLevel());
        self::assertLessThan(ReviewStatus::HIDDEN->getLevel(), ReviewStatus::PUBLISHED->getLevel());
        self::assertLessThan(ReviewStatus::REJECTED->getLevel(), ReviewStatus::HIDDEN->getLevel());
    }

    public function testGetDefault(): void
    {
        self::assertSame(ReviewStatus::PENDING, ReviewStatus::getDefault());
    }
}
