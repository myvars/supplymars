<?php

namespace App\Audit\Application\EventListener;

use App\Audit\Infrastructure\Logging\StatusChangeLogWriter;
use App\Order\Domain\Model\Order\Event\OrderItemStatusWasChangedEvent;
use App\Order\Domain\Model\Order\Event\OrderStatusWasChangedEvent;
use App\Purchasing\Domain\Model\PurchaseOrder\Event\PurchaseOrderItemStatusWasChangedEvent;
use App\Purchasing\Domain\Model\PurchaseOrder\Event\PurchaseOrderStatusWasChangedEvent;
use App\Shared\Application\Identity\PublicIdResolverRegistry;
use App\Shared\Domain\Event\StatusWasChangedEventInterface;
use App\Shared\Infrastructure\Security\CurrentUserProvider;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Listens for status change events and logs them.
 */
#[AsEventListener(event: OrderStatusWasChangedEvent::class)]
#[AsEventListener(event: OrderItemStatusWasChangedEvent::class)]
#[AsEventListener(event: PurchaseOrderStatusWasChangedEvent::class)]
#[AsEventListener(event: PurchaseOrderItemStatusWasChangedEvent::class)]
final readonly class StatusChangeLogger
{
    public function __construct(
        private StatusChangeLogWriter $changeLogWriter,
        private CurrentUserProvider $currentUserProvider,
        private PublicIdResolverRegistry $publicIdResolverRegistry,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(StatusWasChangedEventInterface $event): void
    {
        $legacyId = $this->publicIdResolverRegistry->resolve($event->getId()); // VO in, ?int out
        if (null === $legacyId) {
            $this->logger->warning('Could not resolve legacy ID for public ID', [
                'publicId' => (string) $event->getId(),
                'eventClass' => $event::class,
            ]);

            return;
        }

        $this->changeLogWriter->write(
            type: $event->getType(),
            entityId: $legacyId,
            statusChange: $event->getStatusChange(),
            occurredAt: $event->getOccurredAt(),
            currentUser: $this->currentUserProvider->get(),
        );
    }
}
