<?php

namespace App\Tests\Review\Domain;

use App\Review\Domain\Model\Review\ReviewStatus;
use PHPUnit\Framework\TestCase;

class ReviewStatusTransitionTest extends TestCase
{
    public function testPendingCanTransitionToPublished(): void
    {
        self::assertTrue(ReviewStatus::PENDING->canTransitionTo(ReviewStatus::PUBLISHED));
    }

    public function testPendingCanTransitionToRejected(): void
    {
        self::assertTrue(ReviewStatus::PENDING->canTransitionTo(ReviewStatus::REJECTED));
    }

    public function testPendingCannotTransitionToHidden(): void
    {
        self::assertFalse(ReviewStatus::PENDING->canTransitionTo(ReviewStatus::HIDDEN));
    }

    public function testPendingCannotTransitionToPending(): void
    {
        self::assertFalse(ReviewStatus::PENDING->canTransitionTo(ReviewStatus::PENDING));
    }

    public function testPublishedCanTransitionToHidden(): void
    {
        self::assertTrue(ReviewStatus::PUBLISHED->canTransitionTo(ReviewStatus::HIDDEN));
    }

    public function testPublishedCannotTransitionToRejected(): void
    {
        self::assertFalse(ReviewStatus::PUBLISHED->canTransitionTo(ReviewStatus::REJECTED));
    }

    public function testPublishedCannotTransitionToPending(): void
    {
        self::assertFalse(ReviewStatus::PUBLISHED->canTransitionTo(ReviewStatus::PENDING));
    }

    public function testPublishedCannotTransitionToPublished(): void
    {
        self::assertFalse(ReviewStatus::PUBLISHED->canTransitionTo(ReviewStatus::PUBLISHED));
    }

    public function testHiddenCanTransitionToPublished(): void
    {
        self::assertTrue(ReviewStatus::HIDDEN->canTransitionTo(ReviewStatus::PUBLISHED));
    }

    public function testHiddenCannotTransitionToRejected(): void
    {
        self::assertFalse(ReviewStatus::HIDDEN->canTransitionTo(ReviewStatus::REJECTED));
    }

    public function testHiddenCannotTransitionToPending(): void
    {
        self::assertFalse(ReviewStatus::HIDDEN->canTransitionTo(ReviewStatus::PENDING));
    }

    public function testRejectedCannotTransitionToAnyStatus(): void
    {
        foreach (ReviewStatus::cases() as $status) {
            self::assertFalse(ReviewStatus::REJECTED->canTransitionTo($status));
        }
    }

    public function testAllowEditForPending(): void
    {
        self::assertTrue(ReviewStatus::PENDING->allowEdit());
    }

    public function testAllowEditForPublished(): void
    {
        self::assertTrue(ReviewStatus::PUBLISHED->allowEdit());
    }

    public function testDisallowEditForRejected(): void
    {
        self::assertFalse(ReviewStatus::REJECTED->allowEdit());
    }

    public function testDisallowEditForHidden(): void
    {
        self::assertFalse(ReviewStatus::HIDDEN->allowEdit());
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
