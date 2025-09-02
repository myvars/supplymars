<?php

namespace App\EventListener;

use App\Event\OrderItemStatusWasChangedEvent;
use App\Event\OrderStatusWasChangedEvent;
use App\Event\PurchaseOrderItemStatusWasChangedEvent;
use App\Event\PurchaseOrderStatusWasChangedEvent;
use App\Event\StatusWasChangedEventInterface;
use App\Service\OrderProcessing\StatusChangedLogger;
use App\Service\Utility\CurrentUserProvider;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Listens for status change events and logs them.
 */
#[AsEventListener(event: OrderStatusWasChangedEvent::class)]
#[AsEventListener(event: OrderItemStatusWasChangedEvent::class)]
#[AsEventListener(event: PurchaseOrderStatusWasChangedEvent::class)]
#[AsEventListener(event: PurchaseOrderItemStatusWasChangedEvent::class)]
final readonly class LogStatusWasChanged
{
    public function __construct(
        private StatusChangedLogger $statusChangedLogger,
        private CurrentUserProvider $currentUserProvider,
        private PublicIdResolverRegistry $publicIdResolverRegistry,
        private LoggerInterface $logger
    ) {
    }

    public function __invoke(StatusWasChangedEventInterface $event): void
    {
        $legacyId = $this->publicIdResolverRegistry->resolve($event->publicId()); // VO in, ?int out
        if ($legacyId === null) {
            $this->logger->warning('Could not resolve legacy ID for public ID', [
                'publicId' => (string) $event->publicId(),
                'eventClass' => get_class($event),
            ]);

            return;
        }

        $this->statusChangedLogger->fromStatusWasChangedEvent(
            $event,
            $this->currentUserProvider->get(),
            $legacyId
        );
    }
}
