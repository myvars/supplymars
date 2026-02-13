<?php

namespace App\Note\Domain\Model\Ticket;

enum TicketStatus: string
{
    case OPEN = 'OPEN';
    case REPLIED = 'REPLIED';
    case CLOSED = 'CLOSED';

    public static function getDefault(): self
    {
        return self::OPEN;
    }

    public function getLevel(): int
    {
        return match ($this) {
            self::OPEN => 1,
            self::REPLIED => 2,
            self::CLOSED => 3,
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return match ($this) {
            self::OPEN => match ($to) {
                self::REPLIED, self::CLOSED => true,
                default => false,
            },
            self::REPLIED => match ($to) {
                self::OPEN, self::CLOSED => true,
                default => false,
            },
            self::CLOSED => match ($to) {
                self::OPEN => true,
                default => false,
            },
        };
    }

    public function allowClose(): bool
    {
        return $this !== self::CLOSED;
    }

    public function allowReopen(): bool
    {
        return $this === self::CLOSED;
    }

    public function isClosed(): bool
    {
        return $this === self::CLOSED;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::OPEN => 'Open',
            self::REPLIED => 'Replied',
            self::CLOSED => 'Closed',
        };
    }
}
