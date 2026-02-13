<?php

namespace App\Review\Domain\Model\Review;

enum ReviewStatus: string
{
    case PENDING = 'PENDING';
    case PUBLISHED = 'PUBLISHED';
    case REJECTED = 'REJECTED';
    case HIDDEN = 'HIDDEN';

    public static function getDefault(): self
    {
        return self::PENDING;
    }

    public function canTransitionTo(self $to): bool
    {
        return match ($this) {
            self::PENDING => match ($to) {
                self::PUBLISHED, self::REJECTED => true,
                default => false,
            },
            self::PUBLISHED => match ($to) {
                self::HIDDEN => true,
                default => false,
            },
            self::HIDDEN => match ($to) {
                self::PUBLISHED => true,
                default => false,
            },
            self::REJECTED => false,
        };
    }

    public function allowEdit(): bool
    {
        return self::PENDING === $this || self::PUBLISHED === $this;
    }

    public function getLevel(): int
    {
        return match ($this) {
            self::PENDING => 1,
            self::PUBLISHED => 2,
            self::HIDDEN => 3,
            self::REJECTED => 4,
        };
    }
}
