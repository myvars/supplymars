<?php

namespace App\Shared\Application\Service;

use App\Note\Domain\Repository\TicketRepository;
use App\Order\Domain\Repository\OrderRepository;
use App\Purchasing\Domain\Repository\PurchaseOrderRepository;
use App\Review\Domain\Repository\ReviewRepository;
use App\Shared\Infrastructure\Security\CurrentUserProvider;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class SidebarBadgeProvider
{
    public const string KEY_PENDING_ORDERS = 'sidebar.badge.pending_orders';
    public const string KEY_PENDING_REVIEWS = 'sidebar.badge.pending_reviews';
    public const string KEY_REJECTED_POS = 'sidebar.badge.rejected_pos';
    public const string KEY_OVERDUE_ORDERS = 'sidebar.badge.overdue_orders';
    public const string KEY_MY_QUEUE_PREFIX = 'sidebar.badge.my_queue.';

    private const int TTL_LONG = 3600;
    private const int TTL_SHORT = 300;

    public function __construct(
        private readonly CacheInterface $cache,
        private readonly ReviewRepository $reviewRepository,
        private readonly PurchaseOrderRepository $purchaseOrderRepository,
        private readonly OrderRepository $orderRepository,
        private readonly TicketRepository $ticketRepository,
        private readonly CurrentUserProvider $userProvider,
    ) {
    }

    /** @return array{pendingOrders: int, pendingReviews: int, rejectedPos: int, overdueOrders: int, myQueue: int} */
    public function getCounts(): array
    {
        return [
            'pendingOrders' => $this->getPendingOrderCount(),
            'pendingReviews' => $this->getPendingReviewCount(),
            'rejectedPos' => $this->getRejectedPoCount(),
            'overdueOrders' => $this->getOverdueOrderCount(),
            'myQueue' => $this->getMyQueueCount(),
        ];
    }

    private function getPendingOrderCount(): int
    {
        return $this->cache->get(self::KEY_PENDING_ORDERS, function (ItemInterface $item): int {
            $item->expiresAfter(self::TTL_SHORT);

            return $this->orderRepository->countPendingOrders();
        });
    }

    private function getPendingReviewCount(): int
    {
        return $this->cache->get(self::KEY_PENDING_REVIEWS, function (ItemInterface $item): int {
            $item->expiresAfter(self::TTL_LONG);

            return $this->reviewRepository->countPendingReviews();
        });
    }

    private function getRejectedPoCount(): int
    {
        return $this->cache->get(self::KEY_REJECTED_POS, function (ItemInterface $item): int {
            $item->expiresAfter(self::TTL_LONG);

            return $this->purchaseOrderRepository->countRejectedPurchaseOrders();
        });
    }

    private function getOverdueOrderCount(): int
    {
        return $this->cache->get(self::KEY_OVERDUE_ORDERS, function (ItemInterface $item): int {
            $item->expiresAfter(self::TTL_SHORT);

            return $this->orderRepository->countOverdueOrders();
        });
    }

    private function getMyQueueCount(): int
    {
        if (!$this->userProvider->hasUser()) {
            return 0;
        }

        $userId = $this->userProvider->get()->getId();
        $key = self::KEY_MY_QUEUE_PREFIX . $userId;

        return $this->cache->get($key, function (ItemInterface $item) use ($userId): int {
            $item->expiresAfter(self::TTL_SHORT);

            return $this->ticketRepository->countOpenTicketsForUser($userId);
        });
    }
}
