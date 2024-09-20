<?php

namespace App\Enum;

enum PurchaseOrderStatus: string
{
    case PENDING = 'PENDING';
    case PROCESSING = 'PROCESSING';
    case ACCEPTED = 'ACCEPTED';
    case REJECTED = 'REJECTED';
    case REFUNDED = 'REFUNDED';
    case SHIPPED = 'SHIPPED';
    case DELIVERED = 'DELIVERED';
    case CANCELLED = 'CANCELLED';

    public static function getDefault(): self
    {
        return self::PENDING;
    }

    public function getLevel(): int
    {
        return match ($this) {
            self::PENDING => 1,
            self::PROCESSING => 2,
            self::ACCEPTED => 3,
            self::REJECTED => 4,
            self::REFUNDED => 5,
            self::SHIPPED => 6,
            self::DELIVERED => 7,
            self::CANCELLED => 8,
        };
    }

    public function allowEdit(): bool
    {
        return $this === self::PENDING;
    }

    public function isRefunded(): bool
    {
        return $this === self::REFUNDED;
    }

    public function isCancelled(): bool
    {
        return $this === self::CANCELLED;
    }

    public function canTransitionTo(PurchaseOrderStatus $to): bool
    {
        return match ($this) {
            self::PENDING => match ($to) {
                self::PROCESSING, self::CANCELLED => true,
                default => false,
            },
            self::PROCESSING => match ($to) {
                self::PENDING, self::ACCEPTED, self::REJECTED, self::CANCELLED => true,
                default => false,
            },
            self::ACCEPTED => match ($to) {
                self::REJECTED, self::SHIPPED => true,
                default => false,
            },
            self::REJECTED => match ($to) {
                self::REFUNDED => true,
                default => false,
            },
            self::SHIPPED => match ($to) {
                self::DELIVERED => true,
                default => false,
            },
            self::REFUNDED, self::DELIVERED, self::CANCELLED => false,
        };
    }
}
