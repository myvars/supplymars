<?php

declare(strict_types=1);

namespace App\Order\Application\Listener;

use App\Order\Application\Message\RefreshOrderPage;
use App\Order\Domain\Model\Order\CustomerOrder;
use App\Order\Domain\Model\Order\Event\OrderItemStatusWasChangedEvent;
use App\Order\Domain\Model\Order\Event\OrderItemWasChangedEvent;
use App\Order\Domain\Model\Order\Event\OrderItemWasCreatedEvent;
use App\Order\Domain\Model\Order\Event\OrderItemWasRemovedEvent;
use App\Order\Domain\Model\Order\Event\OrderLockWasChangedEvent;
use App\Order\Domain\Model\Order\Event\OrderStatusWasChangedEvent;
use App\Order\Domain\Model\Order\OrderPublicId;
use App\Order\Domain\Repository\OrderItemRepository;
use App\Order\Domain\Repository\OrderRepository;
use App\Purchasing\Domain\Model\PurchaseOrder\Event\PurchaseOrderItemStatusWasChangedEvent;
use App\Purchasing\Domain\Model\PurchaseOrder\Event\PurchaseOrderItemWasChangedEvent;
use App\Purchasing\Domain\Model\PurchaseOrder\Event\PurchaseOrderItemWasCreatedEvent;
use App\Purchasing\Domain\Model\PurchaseOrder\Event\PurchaseOrderItemWasRemovedEvent;
use App\Purchasing\Domain\Model\PurchaseOrder\Event\PurchaseOrderStatusWasChangedEvent;
use App\Purchasing\Domain\Model\PurchaseOrder\Event\PurchaseOrderWasCreatedEvent;
use App\Purchasing\Domain\Model\PurchaseOrder\Event\PurchaseOrderWasRewoundEvent;
use App\Purchasing\Domain\Repository\PurchaseOrderItemRepository;
use App\Purchasing\Domain\Repository\PurchaseOrderRepository;
use App\Shared\Application\LiveUpdatePublisherInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DeduplicateStamp;

/**
 * Everyone with an order's page open sees it change without reloading: its items, its purchase orders and
 * their items, its status, totals and lock. The page refreshes rather than receiving new HTML, because what it
 * shows depends on the viewer (the lock button, the actions).
 *
 * The events are sync, and one flush can raise hundreds (a simulation command flushes once per run), so each
 * queues a refresh for its order, and the queue holds at most one per order: the hub is called once per order,
 * by the worker, never by the request or the scheduled job. See Docs/patterns/Turbo/README.md#live-page-updates.
 */
#[AsEventListener(event: OrderStatusWasChangedEvent::class, method: 'onOrderChanged')]
#[AsEventListener(event: OrderLockWasChangedEvent::class, method: 'onOrderChanged')]
#[AsEventListener(event: OrderItemWasRemovedEvent::class, method: 'onOrderChanged')]
#[AsEventListener(event: PurchaseOrderItemWasRemovedEvent::class, method: 'onOrderChanged')]
#[AsEventListener(event: OrderItemWasCreatedEvent::class, method: 'onOrderItemChanged')]
#[AsEventListener(event: OrderItemWasChangedEvent::class, method: 'onOrderItemChanged')]
#[AsEventListener(event: OrderItemStatusWasChangedEvent::class, method: 'onOrderItemChanged')]
#[AsEventListener(event: PurchaseOrderWasCreatedEvent::class, method: 'onPurchaseOrderChanged')]
#[AsEventListener(event: PurchaseOrderStatusWasChangedEvent::class, method: 'onPurchaseOrderChanged')]
#[AsEventListener(event: PurchaseOrderWasRewoundEvent::class, method: 'onPurchaseOrderChanged')]
#[AsEventListener(event: PurchaseOrderItemWasCreatedEvent::class, method: 'onPurchaseOrderItemChanged')]
#[AsEventListener(event: PurchaseOrderItemWasChangedEvent::class, method: 'onPurchaseOrderItemChanged')]
#[AsEventListener(event: PurchaseOrderItemStatusWasChangedEvent::class, method: 'onPurchaseOrderItemChanged')]
final readonly class RefreshOrderPageForViewers
{
    public function __construct(
        private OrderRepository $orders,
        private OrderItemRepository $orderItems,
        private PurchaseOrderRepository $purchaseOrders,
        private PurchaseOrderItemRepository $purchaseOrderItems,
        private MessageBusInterface $bus,
        private LiveUpdatePublisherInterface $publisher,
    ) {
    }

    /**
     * @throws ExceptionInterface
     */
    public function onOrderChanged(
        OrderStatusWasChangedEvent|OrderLockWasChangedEvent|OrderItemWasRemovedEvent|PurchaseOrderItemWasRemovedEvent $event,
    ): void {
        $this->queueRefresh($this->orders->getByPublicId($event->getId()));
    }

    /**
     * @throws ExceptionInterface
     */
    public function onOrderItemChanged(OrderItemWasCreatedEvent|OrderItemWasChangedEvent|OrderItemStatusWasChangedEvent $event): void
    {
        $this->queueRefresh($this->orderItems->getByPublicId($event->getId())?->getCustomerOrder());
    }

    /**
     * @throws ExceptionInterface
     */
    public function onPurchaseOrderChanged(
        PurchaseOrderWasCreatedEvent|PurchaseOrderStatusWasChangedEvent|PurchaseOrderWasRewoundEvent $event,
    ): void {
        $this->queueRefresh($this->purchaseOrders->getByPublicId($event->getId())?->getCustomerOrder());
    }

    /**
     * @throws ExceptionInterface
     */
    public function onPurchaseOrderItemChanged(
        PurchaseOrderItemWasCreatedEvent|PurchaseOrderItemWasChangedEvent|PurchaseOrderItemStatusWasChangedEvent $event,
    ): void {
        $this->queueRefresh($this->purchaseOrderItems->getByPublicId($event->getId())?->getPurchaseOrder()->getCustomerOrder());
    }

    #[AsMessageHandler]
    public function refresh(RefreshOrderPage $message): void
    {
        $this->publisher->refresh(CustomerOrder::liveTopic(OrderPublicId::fromString($message->orderId)));
    }

    /**
     * @throws ExceptionInterface
     */
    private function queueRefresh(?CustomerOrder $order): void
    {
        if (!$order instanceof CustomerOrder) {
            return;
        }

        $orderId = $order->getPublicId()->value();

        // Skipped while one for the order is queued. Released when the worker receives it, not when it is
        // handled, so a change made after that queues another; the TTL frees it if the worker is down
        $this->bus->dispatch(new RefreshOrderPage($orderId), [
            new DeduplicateStamp('order-page-refresh.' . $orderId, onlyDeduplicateInQueue: true),
        ]);
    }
}
