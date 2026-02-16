<?php

namespace App\Shared\Application\Listener;

use App\Order\Domain\Model\Order\Event\OrderStatusWasChangedEvent;
use App\Order\Domain\Model\Order\OrderStatus;
use App\Purchasing\Domain\Model\PurchaseOrder\Event\PurchaseOrderStatusWasChangedEvent;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;
use App\Review\Domain\Model\Review\Event\ReviewStatusWasChangedEvent;
use App\Review\Domain\Model\Review\Event\ReviewWasCreatedEvent;
use App\Review\Domain\Model\Review\ReviewStatus;
use App\Shared\Application\Service\SidebarBadgeProvider;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Cache\CacheInterface;

#[AsEventListener(event: ReviewWasCreatedEvent::class, method: 'onReviewCreated')]
#[AsEventListener(event: ReviewStatusWasChangedEvent::class, method: 'onReviewStatusChanged')]
#[AsEventListener(event: PurchaseOrderStatusWasChangedEvent::class, method: 'onPurchaseOrderStatusChanged')]
#[AsEventListener(event: OrderStatusWasChangedEvent::class, method: 'onOrderStatusChanged')]
final readonly class SidebarBadgeCacheInvalidator
{
    public function __construct(
        private CacheInterface $cache,
    ) {
    }

    public function onReviewCreated(ReviewWasCreatedEvent $event): void
    {
        $this->cache->delete(SidebarBadgeProvider::KEY_PENDING_REVIEWS);
    }

    public function onReviewStatusChanged(ReviewStatusWasChangedEvent $event): void
    {
        $change = $event->getStatusChange();

        if ($change->before() === ReviewStatus::PENDING || $change->after() === ReviewStatus::PENDING) {
            $this->cache->delete(SidebarBadgeProvider::KEY_PENDING_REVIEWS);
        }
    }

    public function onPurchaseOrderStatusChanged(PurchaseOrderStatusWasChangedEvent $event): void
    {
        $change = $event->getStatusChange();

        if ($change->before() === PurchaseOrderStatus::REJECTED || $change->after() === PurchaseOrderStatus::REJECTED) {
            $this->cache->delete(SidebarBadgeProvider::KEY_REJECTED_POS);
        }
    }

    public function onOrderStatusChanged(OrderStatusWasChangedEvent $event): void
    {
        $change = $event->getStatusChange();
        $terminal = [OrderStatus::DELIVERED, OrderStatus::CANCELLED];

        if (in_array($change->before(), $terminal, true) || in_array($change->after(), $terminal, true)) {
            $this->cache->delete(SidebarBadgeProvider::KEY_OVERDUE_ORDERS);
        }
    }
}
