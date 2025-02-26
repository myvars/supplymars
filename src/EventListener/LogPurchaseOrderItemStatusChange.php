<?php

namespace App\EventListener;

use App\Event\PurchaseOrderItemStatusChangedEvent;
use App\Service\OrderProcessing\StatusChangeLogger;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class LogPurchaseOrderItemStatusChange
{
    public function __construct(private StatusChangeLogger $statusChangeLogger)
    {
    }

    #[AsEventListener]
    public function onPurchaseOrderItemStatusChange(PurchaseOrderItemStatusChangedEvent $event): void
    {
        $this->statusChangeLogger->fromStatusChangeEvent(
            $event,
            $event->getPurchaseOrderItem()->getId(),
            $event->getPurchaseOrderItem()->getStatus()->value
        );
    }
}
