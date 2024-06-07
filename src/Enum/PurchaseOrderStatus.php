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
                self::SHIPPED, self::CANCELLED => true,
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
