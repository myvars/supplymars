<?php

declare(strict_types=1);

namespace App\Audit\Application\EventListener;

use App\Audit\Infrastructure\Logging\SupplierStockChangeLogWriter;
use App\Purchasing\Domain\Model\SupplierProduct\Event\SupplierProductStockWasChangedEvent;
use App\Purchasing\UI\Http\ArgumentResolver\SupplierProductPublicIdResolver;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Listens for supplier stock change events and logs them.
 */
#[AsEventListener(event: SupplierProductStockWasChangedEvent::class)]
final readonly class SupplierStockChangeLogger
{
    public function __construct(
        private SupplierStockChangeLogWriter $changeLogWriter,
        private SupplierProductPublicIdResolver $publicIdResolver,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(SupplierProductStockWasChangedEvent $event): void
    {
        $legacyId = $this->publicIdResolver->resolve($event->getId()); // VO in, ?int out
        if (null === $legacyId) {
            $this->logger->warning('Could not resolve legacy ID for public ID', [
                'publicId' => (string) $event->getId(),
                'eventClass' => $event::class,
            ]);

            return;
        }

        $this->changeLogWriter->write(
            type: $event->getType(),
            supplierProductId: $legacyId,
            stockChange: $event->getStockChange(),
            costChange: $event->getCostChange(),
            occurredAt: $event->getOccurredAt(),
        );
    }
}
