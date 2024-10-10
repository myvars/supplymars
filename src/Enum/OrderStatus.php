<?php

namespace App\Enum;

enum OrderStatus: string
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

    public static function fromValue(string $value): self
    {
        return match ($value) {
            'PENDING' => self::PENDING,
            'PROCESSING' => self::PROCESSING,
            'SHIPPED' => self::SHIPPED,
            'DELIVERED' => self::DELIVERED,
            'CANCELLED' => self::CANCELLED,
            default => throw new \InvalidArgumentException('Invalid OrderStatus value: ' . $value),
        };
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

    public static function getMappedOrderStatusFromPurchaseOrder(PurchaseOrderStatus $purchaseOrderStatus): self
    {
        return match ($purchaseOrderStatus) {
            PurchaseOrderStatus::PENDING => self::PENDING,
            PurchaseOrderStatus::PROCESSING,
            PurchaseOrderStatus::ACCEPTED,
            PurchaseOrderStatus::REJECTED,
            PurchaseOrderStatus::REFUNDED => self::PROCESSING,
            PurchaseOrderStatus::SHIPPED => self::SHIPPED,
            PurchaseOrderStatus::DELIVERED => self::DELIVERED,
            PurchaseOrderStatus::CANCELLED => self::CANCELLED,
        };
    }

    public function allowEdit(): bool
    {
        return $this === self::PENDING || $this === self::PROCESSING;
    }

    public function allowCancel(): bool
    {
        return $this === self::PENDING;
    }

    public function isCancelled(): bool
    {
        return $this === self::CANCELLED;
    }

    public function canTransitionTo(OrderStatus $to): bool
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
