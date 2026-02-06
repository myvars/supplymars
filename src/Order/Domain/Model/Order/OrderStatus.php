<?php

namespace App\Order\Domain\Model\Order;

use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;

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

    public function getChartColor(): string
    {
        return match ($this) {
            self::PENDING => '#6b728090',    // gray-500
            self::PROCESSING => '#eab30890', // yellow-500
            self::SHIPPED => '#3b82f690',    // blue-500
            self::DELIVERED => '#22c55e90',  // green-500
            self::CANCELLED => '#ef444490',  // red-500
        };
    }

    public function allowEdit(): bool
    {
        return self::PENDING === $this || self::PROCESSING === $this;
    }

    public function allowCancel(): bool
    {
        return self::PENDING === $this;
    }

    public function isCancelled(): bool
    {
        return self::CANCELLED === $this;
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
