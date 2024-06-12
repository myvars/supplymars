<?php

namespace App\Enum;

enum PurchaseOrderStatus: string
{
    case PENDING = 'PENDING';
    case PROCESSING = 'PROCESSING';
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
            self::SHIPPED => 3,
            self::DELIVERED => 4,
            self::CANCELLED => 5,
        };
    }

    public function allowEdit(): bool
    {
        return $this === self::PENDING;
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
                self::PENDING, self::SHIPPED, self::CANCELLED => true,
                default => false,
            },
            self::SHIPPED => match ($to) {
                self::DELIVERED => true,
                default => false,
            },
            self::DELIVERED, self::CANCELLED => false,
        };
    }
}
